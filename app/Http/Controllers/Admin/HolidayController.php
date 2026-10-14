<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Holiday;
use App\Models\Institute;
use App\Services\HolidayService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayController extends AdminController
{
    public function __construct(
        protected HolidayService $holidayService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('holiday.view');

        $filters = $this->listFilterVars($request, ['branches']);
        $instituteId = $this->filterInstituteId($request);

        $holidays = Holiday::query()
            ->with(['branches:id,name', 'batches:id,name', 'creator:id,name'])
            ->when($instituteId, fn ($q) => $q->where('institute_id', $instituteId))
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $branchId = (int) $request->input('branch_id');
                $q->where(function ($sq) use ($branchId) {
                    $sq->whereDoesntHave('branches')
                        ->orWhereHas('branches', fn ($bq) => $bq->where('branches.id', $branchId));
                });
            })
            ->when(BranchScope::for()->isRestricted(), function ($q) {
                $ids = BranchScope::for()->branchIds();
                $q->where(function ($sq) use ($ids) {
                    $sq->whereDoesntHave('branches')
                        ->orWhereHas('branches', fn ($bq) => $bq->whereIn('branches.id', $ids));
                });
            })
            ->orderByDesc('start_date')
            ->get();

        $formInstituteId = $instituteId
            ?: ($this->isPlatform() ? (int) ($this->instituteOptions()->first()?->id ?: 0) ?: null : null);

        return view('admin.holiday.holiday', array_merge($filters, [
            'holidays' => $holidays,
            'institutes' => $this->instituteOptions(),
            'modalBranches' => $formInstituteId ? BranchScope::for()->branchesForInstitute($formInstituteId) : collect(),
            'batches' => $formInstituteId
                ? Batch::query()->where('institute_id', $formInstituteId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'branch_id'])
                : collect(),
            'defaultInstituteId' => $formInstituteId,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('holiday.add');
        $validated = $this->validateHoliday($request);
        $holiday = $this->holidayService->create($validated);

        return Ajax::success('Holiday saved.', $holiday, 201);
    }

    public function update(Request $request, Holiday $holiday): JsonResponse
    {
        $this->ensureCan('holiday.edit');
        $this->assertVisible($holiday);
        $validated = $this->validateHoliday($request, $holiday);
        $holiday = $this->holidayService->update($holiday, $validated);

        return Ajax::success('Holiday updated.', $holiday);
    }

    public function destroy(Request $request, Holiday $holiday): JsonResponse|RedirectResponse
    {
        $this->ensureCan('holiday.delete');
        $this->assertVisible($holiday);
        $this->holidayService->delete($holiday);

        return $this->respondDestroyed($request, 'Holiday deleted.', 'admin.holiday');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateHoliday(Request $request, ?Holiday $holiday = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        $branchIds = BranchScope::for()->allowedBranchIdsForInstitute($instituteId);

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($branchIds)],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => [
                'integer',
                Rule::exists('batches', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)),
            ],
            'title' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    protected function instituteOptions()
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return Institute::query()->where('id', $user->institute_id)->get(['id', 'name']);
        }

        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function resolveInstituteId(): ?int
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return (int) $user->institute_id;
        }

        return null;
    }

    protected function assertVisible(Holiday $holiday): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $holiday->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if (BranchScope::for($user)->isRestricted()) {
            $ids = BranchScope::for($user)->branchIds();
            $hasBranches = $holiday->branches()->exists();
            if ($hasBranches && ! $holiday->branches()->whereIn('branches.id', $ids)->exists()) {
                abort(404);
            }
        }
    }

    protected function ensureCan(string $permission): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can($permission))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
