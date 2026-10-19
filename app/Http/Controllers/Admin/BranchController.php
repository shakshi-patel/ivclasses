<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Institute;
use App\Services\Billing\PlanLimitService;
use App\Services\BranchService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends AdminController
{
    public function __construct(
        protected BranchService $branchService,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('branch.view');
        $query = BranchScope::for()
            ->applyToBranches(Branch::query())
            ->with(['institute:id,name', 'manager:id,name,email']);

        $this->applyInstituteFilter($query, $request);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $branches = $query->latest()->get();
        $filters = $this->listFilterVars($request, []);

        return view('admin.master.branch.branch', array_merge(compact('branches'), $filters));
    }

    public function create(): View
    {
        $this->ensureCan('branch.add');

        return view('admin.master.branch.add_branch', [
            'branch' => null,
            'institutes' => $this->instituteOptions(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('branch.add');

        $validated = $this->validateBranch($request);
        $instituteId = (int) $validated['institute_id'];

        if ($limitError = $this->branchLimitError($instituteId)) {
            if ($request->expectsJson()) {
                return Ajax::error($limitError, null, 403);
            }

            return back()->withInput()->withErrors(['institute_id' => $limitError]);
        }

        $branch = $this->branchService->create($validated);

        if ($request->expectsJson()) {
            return Ajax::success('Branch created. Manager can sign in via Branch portal.', $branch, 201);
        }

        return redirect()
            ->route('admin.master.branch')
            ->with('success', 'Branch created. Manager can sign in via Branch portal.');
    }

    public function edit(Branch $branch): View
    {
        $this->ensureCan('branch.edit');
        $this->assertVisible($branch);
        $branch->load('manager');

        return view('admin.master.branch.add_branch', [
            'branch' => $branch,
            'institutes' => $this->instituteOptions(),
        ]);
    }

    public function update(Request $request, Branch $branch): JsonResponse|RedirectResponse
    {
        $this->ensureCan('branch.edit');
        $this->assertVisible($branch);

        $validated = $this->validateBranch($request, $branch);
        $branch = $this->branchService->update($branch, $validated);

        if ($request->expectsJson()) {
            return Ajax::success('Branch updated.', $branch);
        }

        return redirect()
            ->route('admin.master.branch')
            ->with('success', 'Branch updated.');
    }

    public function destroy(Request $request, Branch $branch): JsonResponse|RedirectResponse
    {
        $this->ensureCan('branch.delete');
        $this->assertVisible($branch);

        $batchCount = \App\Models\Batch::query()->where('branch_id', $branch->id)->count();
        $studentCount = \App\Models\Student::query()->where('branch_id', $branch->id)->count();

        if ($batchCount > 0 || $studentCount > 0) {
            return $this->respondDestroyFailed(
                $request,
                "Cannot delete branch: {$batchCount} batch(es) and {$studentCount} student(s) are still linked. Reassign or remove them first."
            );
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($branch) {
            $managerId = $branch->manager_user_id;

            \Illuminate\Support\Facades\DB::table('branch_user')
                ->where('branch_id', $branch->id)
                ->delete();

            $branch->update(['manager_user_id' => null]);
            $branch->delete();

            if ($managerId) {
                $stillManages = Branch::query()->where('manager_user_id', $managerId)->exists();
                $stillLinked = \Illuminate\Support\Facades\DB::table('branch_user')
                    ->where('user_id', $managerId)
                    ->exists();

                if (! $stillManages && ! $stillLinked) {
                    \App\Models\User::query()->whereKey($managerId)->update(['is_active' => false]);
                }
            }
        });

        return $this->respondDestroyed($request, 'Branch deleted.', 'admin.master.branch');
    }

    public function toggleStatus(Branch $branch): JsonResponse
    {
        $this->ensureCan('branch.status');
        $this->assertVisible($branch);

        $branch->update([
            'status' => $branch->status === 'active' ? 'inactive' : 'active',
        ]);

        return Ajax::success(
            $branch->status === 'active' ? 'Branch activated.' : 'Branch deactivated.',
            ['id' => $branch->id, 'status' => $branch->status]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateBranch(Request $request, ?Branch $branch = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        $nameUnique = Rule::unique('branches', 'name')
            ->where(fn ($q) => $q->where('institute_id', $instituteId)->whereNull('deleted_at'));

        if ($branch) {
            $nameUnique->ignore($branch->id);
        }

        $emailUnique = Rule::unique('users', 'email');
        if ($branch?->manager_user_id) {
            $emailUnique->ignore($branch->manager_user_id);
        }

        $rules = [
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'name' => ['required', 'string', 'max:150', $nameUnique],
            'code' => ['nullable', 'string', 'max:40'],
            'manager_name' => ['required', 'string', 'max:150'],
            'manager_email' => ['required', 'email', 'max:150', $emailUnique],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive'],
        ];

        if ($branch) {
            $rules['manager_password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['manager_password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules);
    }

    protected function branchLimitError(int $instituteId): ?string
    {
        $institute = Institute::query()->with('currentSubscription.plan')->find($instituteId);

        return app(PlanLimitService::class)->branchLimitError($institute);
    }

    protected function instituteOptions()
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return Institute::query()->where('id', $user->institute_id)->get(['id', 'name']);
        }

        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function assertVisible(Branch $branch): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $branch->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        BranchScope::for($user)->assertCanAccessBranch($branch->id);
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
