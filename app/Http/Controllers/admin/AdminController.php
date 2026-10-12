<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Institute;
use App\Models\Standard;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Shared helpers for CRM admin controllers (platform + institute).
 */
abstract class AdminController extends Controller
{
    protected function ensureCan(string $permission): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can($permission))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function ensureCanAny(array $permissions): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->canAny($permissions))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    protected function isPlatform(): bool
    {
        return (bool) auth()->user()?->isPlatformUser();
    }

    /**
     * Institute id for list filters: platform uses request; institute users use their own.
     */
    protected function filterInstituteId(Request $request): ?int
    {
        if ($this->isPlatform()) {
            return $request->filled('institute_id') ? (int) $request->input('institute_id') : null;
        }

        $id = auth()->user()?->institute_id;

        return $id ? (int) $id : null;
    }

    protected function applyInstituteFilter(Builder $query, Request $request, ?string $column = null): void
    {
        if (! $this->isPlatform() || ! $request->filled('institute_id')) {
            return;
        }

        $query->where($column ?? 'institute_id', (int) $request->input('institute_id'));
    }

    protected function filterInstitutes(): Collection
    {
        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function filterBranches(?int $instituteId = null): Collection
    {
        if (! $instituteId) {
            return collect();
        }

        return BranchScope::for()->branchesForInstitute($instituteId);
    }

    protected function filterStandards(?int $instituteId = null): Collection
    {
        if (! $instituteId) {
            return collect();
        }

        return Standard::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function filterBatches(?int $instituteId = null, ?int $branchId = null): Collection
    {
        if (! $instituteId) {
            return collect();
        }

        $query = Batch::query()
            ->where('institute_id', $instituteId)
            ->orderBy('name');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get(['id', 'name', 'branch_id'])->unique('id')->values();
    }

    /**
     * Shared vars for list filter Blade component.
     *
     * @param  list<string>  $needs  branches|standards|batches
     * @return array<string, mixed>
     */
    protected function listFilterVars(Request $request, array $needs = ['branches']): array
    {
        $instituteId = $this->filterInstituteId($request);
        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $vars = [
            'isPlatform' => $this->isPlatform(),
            'filterInstituteId' => $instituteId,
            'institutes' => $this->isPlatform() ? $this->filterInstitutes() : collect(),
        ];

        if (in_array('branches', $needs, true)) {
            $vars['branches'] = $this->filterBranches($instituteId);
        }

        if (in_array('standards', $needs, true)) {
            $vars['standards'] = $this->filterStandards($instituteId);
        }

        if (in_array('batches', $needs, true)) {
            // All institute batches (JS can narrow by branch); server also respects branch when filtering rows.
            $vars['batches'] = $this->filterBatches($instituteId);
        }

        return $vars;
    }

    /**
     * Dual response for destroy (and similar mutations): JSON for AdminAjax, redirect otherwise.
     */
    protected function respondDestroyed(
        Request $request,
        string $message,
        string $redirectRoute,
        mixed $data = null
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return Ajax::success($message, $data);
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', $message);
    }

    protected function respondDestroyFailed(
        Request $request,
        string $message,
        int $status = 422
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return Ajax::error($message, null, $status);
        }

        return redirect()
            ->back()
            ->with('error', $message);
    }
}
