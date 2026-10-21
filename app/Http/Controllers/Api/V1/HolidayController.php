<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\HolidayResource;
use App\Models\Holiday;
use App\Services\HolidayService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends ApiController
{
    public function __construct(
        protected HolidayService $holidayService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($this->actor()->isParent()) {
            $this->ensureCanAny(['holiday.view', 'parent_dashboard.list_holidays']);
        } else {
            $this->ensureCan('holiday.view');
        }

        $query = $this->holidayQuery()
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['branches:id,name', 'batches:id,name', 'creator:id,name']);

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            $query->where(function ($sq) use ($branchId) {
                $sq->whereDoesntHave('branches')
                    ->orWhereHas('branches', fn ($bq) => $bq->where('branches.id', $branchId));
            });
        }

        return $this->paginated(
            'OK',
            $query->orderByDesc('start_date')->orderByDesc('id')->paginate($this->perPage())
                ->through(fn (Holiday $holiday) => (new HolidayResource($holiday))->resolve($request))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('holiday.add');

        $holiday = $this->holidayService->create($this->validateHoliday($request));

        return Ajax::success('Holiday saved.', (new HolidayResource($holiday))->resolve(), 201);
    }

    public function update(Request $request, Holiday $holiday): JsonResponse
    {
        $this->ensureCan('holiday.edit');
        $this->assertVisible($holiday);

        $holiday = $this->holidayService->update($holiday, $this->validateHoliday($request, $holiday));

        return Ajax::success('Holiday updated.', (new HolidayResource($holiday))->resolve());
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        $this->ensureCan('holiday.delete');
        $this->assertVisible($holiday);
        $this->holidayService->delete($holiday);

        return Ajax::success('Holiday deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateHoliday(Request $request, ?Holiday $holiday = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        $user = $this->actor();
        $branchIds = $user->isTeacher()
            ? \App\Support\OwnBatchScope::branchIds($user)
            : BranchScope::for($user)->allowedBranchIdsForInstitute($instituteId);

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($branchIds ?: [0])],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => array_values(array_filter([
                'integer',
                Rule::exists('batches', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)),
                $user->isTeacher() ? Rule::in(\App\Support\OwnBatchScope::batchIds($user) ?: [0]) : null,
            ])),
            'title' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    protected function holidayQuery()
    {
        $user = $this->actor();
        $query = Holiday::query();

        if ($user->isParent()) {
            return $this->scopeParentHolidays($query, $user);
        }

        if ($user->isTeacher()) {
            return $this->scopeTeacherHolidays($query, $user);
        }

        $scope = BranchScope::for($user);
        if (! $scope->isRestricted()) {
            return $query;
        }

        $ids = $scope->branchIds();

        return $query->where(function ($sq) use ($ids) {
            $sq->whereDoesntHave('branches');
            if ($ids !== []) {
                $sq->orWhereHas('branches', fn ($bq) => $bq->whereIn('branches.id', $ids));
            }
        });
    }

    protected function assertVisible(Holiday $holiday): void
    {
        $user = $this->actor();
        if ((int) $holiday->institute_id !== (int) $user->institute_id) {
            abort(404, 'Holiday not found.');
        }

        if ($user->isParent()) {
            $visible = $this->scopeParentHolidays(Holiday::query()->whereKey($holiday->id), $user)->exists();
            abort_unless($visible, 404, 'Holiday not found.');

            return;
        }

        if ($user->isTeacher()) {
            $visible = $this->scopeTeacherHolidays(Holiday::query()->whereKey($holiday->id), $user)->exists();
            abort_unless($visible, 404, 'Holiday not found.');

            return;
        }

        $scope = BranchScope::for($user);
        if (! $scope->isRestricted()) {
            return;
        }

        $ids = $scope->branchIds();
        $hasBranches = $holiday->branches()->exists();
        if ($hasBranches && ($ids === [] || ! $holiday->branches()->whereIn('branches.id', $ids)->exists())) {
            abort(404, 'Holiday not found.');
        }
    }

    protected function scopeParentHolidays($query, \App\Models\User $user)
    {
        $studentId = $this->parentStudentId();
        $batchIds = \App\Support\ParentBatchScope::batchIds($user, $studentId)->map(fn ($id) => (int) $id)->all();
        $branchIds = \App\Models\Student::query()
            ->whereIn('id', \App\Support\ParentBatchScope::studentIds($user, $studentId)->all() ?: [0])
            ->whereNotNull('branch_id')
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $query->where(function ($q) use ($batchIds, $branchIds) {
            $q->where(function ($inner) {
                $inner->whereDoesntHave('branches')->whereDoesntHave('batches');
            });
            if ($branchIds !== []) {
                $q->orWhereHas('branches', fn ($bq) => $bq->whereIn('branches.id', $branchIds));
            }
            if ($batchIds !== []) {
                $q->orWhereHas('batches', fn ($bq) => $bq->whereIn('batches.id', $batchIds));
            }
        });
    }

    protected function scopeTeacherHolidays($query, \App\Models\User $user)
    {
        $batchIds = \App\Support\OwnBatchScope::batchIds($user);
        $branchIds = \App\Support\OwnBatchScope::branchIds($user);

        return $query->where(function ($q) use ($batchIds, $branchIds) {
            $q->where(function ($inner) {
                $inner->whereDoesntHave('branches')->whereDoesntHave('batches');
            });
            if ($branchIds !== []) {
                $q->orWhereHas('branches', fn ($bq) => $bq->whereIn('branches.id', $branchIds));
            }
            if ($batchIds !== []) {
                $q->orWhereHas('batches', fn ($bq) => $bq->whereIn('batches.id', $batchIds));
            }
        });
    }
}
