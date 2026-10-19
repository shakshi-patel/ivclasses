<?php

namespace App\Http\Controllers\Admin;

use App\Models\Institute;
use App\Models\Standard;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StandardController extends AdminController
{
    public function index(Request $request): View
    {
        $this->ensureCan('standard.view');

        $query = Standard::query()->with('institute:id,name');

        $this->applyInstituteFilter($query, $request);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $standards = $query->orderBy('sort_order')->orderBy('name')->get();
        $filters = $this->listFilterVars($request, []);

        return view('admin.master.standard.standard', array_merge(
            compact('standards'),
            $filters,
            ['institutes' => $this->instituteOptions()]
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('standard.add');

        $validated = $this->validateStandard($request);

        $trashed = Standard::withTrashed()
            ->where('institute_id', $validated['institute_id'])
            ->where('name', $validated['name'])
            ->whereNotNull('deleted_at')
            ->first();

        if ($trashed) {
            $trashed->restore();
            $trashed->update($validated);

            return Ajax::success(
                'Standard restored.',
                $trashed->fresh()->load('institute:id,name')
            );
        }

        $standard = Standard::query()->create($validated);
        $standard->load('institute:id,name');

        return Ajax::success('Standard created.', $standard, 201);
    }

    public function update(Request $request, Standard $standard): JsonResponse
    {
        $this->ensureCan('standard.edit');
        $this->assertVisible($standard);

        $validated = $this->validateStandard($request, $standard);
        $standard->update($validated);

        return Ajax::success('Standard updated.', $standard->fresh()->load('institute:id,name'));
    }

    public function destroy(Request $request, Standard $standard): JsonResponse|RedirectResponse
    {
        $this->ensureCan('standard.delete');
        $this->assertVisible($standard);

        $batchCount = $standard->batches()->count();
        $studentCount = $standard->students()->count();
        $inquiryCount = $standard->inquiries()->count();

        if ($batchCount > 0 || $studentCount > 0 || $inquiryCount > 0) {
            $parts = [];
            if ($batchCount > 0) {
                $parts[] = $batchCount.' batch'.($batchCount === 1 ? '' : 'es');
            }
            if ($studentCount > 0) {
                $parts[] = $studentCount.' student'.($studentCount === 1 ? '' : 's');
            }
            if ($inquiryCount > 0) {
                $parts[] = $inquiryCount.' inquir'.($inquiryCount === 1 ? 'y' : 'ies');
            }

            return $this->respondDestroyFailed(
                $request,
                'Cannot delete — this standard is used by '.implode(', ', $parts).'. Reassign them first.'
            );
        }

        $standard->delete();

        return $this->respondDestroyed($request, 'Standard deleted.', 'admin.master.standard');
    }

    public function toggleStatus(Standard $standard): JsonResponse
    {
        $this->ensureCan('standard.status');
        $this->assertVisible($standard);

        $standard->update([
            'status' => $standard->status === 'active' ? 'inactive' : 'active',
        ]);

        return Ajax::success(
            $standard->status === 'active' ? 'Standard activated.' : 'Standard deactivated.',
            ['id' => $standard->id, 'status' => $standard->status]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateStandard(Request $request, ?Standard $standard = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id', $user?->institute_id);

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        if (! $instituteId) {
            abort(422, 'Institute is required.');
        }

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('standards', 'name')
                    ->where(fn ($q) => $q->where('institute_id', $instituteId)->whereNull('deleted_at'))
                    ->ignore($standard?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', 'in:active,inactive'],
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

    protected function assertVisible(Standard $standard): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $standard->institute_id !== (int) $user->institute_id) {
            abort(404);
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
