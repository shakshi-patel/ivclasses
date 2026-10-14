<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Institute;
use App\Models\LoginLog;
use App\Models\Role;
use App\Models\Staff;
use App\Services\StaffService;
use App\Services\TodoService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class StaffController extends AdminController
{
    public function __construct(
        protected StaffService $staffService,
        protected TodoService $todoService
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $this->ensureCan('staff.view');

        return redirect()->route('admin.users', $request->query());
    }

    public function create(Request $request): View
    {
        $this->ensureCanAddStaff((string) $request->query('role', ''));

        $instituteId = $this->currentInstituteId();
        if ($instituteId) {
            $this->staffService->ensureDefaultRoles($instituteId);
        }

        $roles = $this->roleOptions($instituteId);
        $preset = (string) $request->query('role', '');
        $listRole = in_array($preset, ['principal', 'receptionist', 'accountant'], true) ? $preset : null;
        $staffRoleId = ($listRole && $instituteId)
            ? $roles->firstWhere('name', $listRole)?->id
            : null;

        return view('admin.staff.add_staff', [
            'staffMember' => null,
            'staffRoleId' => $staffRoleId,
            'listRole' => $listRole,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions(),
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateStaff($request);
        $roleName = ! empty($validated['role_id'])
            ? strtolower((string) Role::query()->find($validated['role_id'])?->name)
            : '';
        $this->ensureCanAddStaff($roleName);

        $instituteId = (int) $validated['institute_id'];

        if ($limitError = $this->staffService->staffLimitError($instituteId)) {
            if ($request->expectsJson()) {
                return Ajax::error($limitError, null, 403);
            }

            return back()->withInput()->withErrors(['institute_id' => $limitError]);
        }

        $validated['avatar'] = $request->file('avatar');
        $staff = $this->staffService->create($validated);

        if ($request->expectsJson()) {
            return Ajax::success('Staff created.', $staff, 201);
        }

        return redirect()
            ->route('admin.users', $this->usersTabQuery($staff))
            ->with('success', 'Staff created. They can log in with their email.');
    }

    public function show(Request $request, Staff $staff): View
    {
        $this->ensureCanManageStaff($staff, 'show');
        $this->assertVisible($staff);

        $staff->load(['user', 'institute', 'branches']);
        $staffRole = $this->staffService->resolveRole($staff);

        $loginLogs = LoginLog::query()
            ->where('user_id', $staff->user_id)
            ->orderByDesc('logged_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'login_page');
        $loginLogs->appends($request->except('login_page') + ['tab' => 'login']);

        $assignedTodos = $staff->user_id
            ? $this->todoService->paginateForAssignee((int) $staff->user_id, (int) $staff->institute_id, 8, 'work_page')
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 8, 1, [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'work_page',
            ]);
        $assignedTodos->appends($request->except('work_page') + ['tab' => 'work']);

        return view('admin.staff.view_staff', [
            'staffMember' => $staff,
            'staffRole' => $staffRole,
            'loginLogs' => $loginLogs,
            'assignedTodos' => $assignedTodos,
        ]);
    }

    public function edit(Staff $staff): View
    {
        $this->ensureCanManageStaff($staff, 'edit');
        $this->assertVisible($staff);

        $staff->load(['user', 'branches']);
        $this->staffService->ensureDefaultRoles((int) $staff->institute_id);
        $staffRole = $this->staffService->resolveRole($staff);

        $roleName = strtolower((string) ($staffRole['name'] ?? ''));

        return view('admin.staff.add_staff', [
            'staffMember' => $staff,
            'staffRoleId' => $staffRole['id'],
            'listRole' => in_array($roleName, ['branch_manager', 'principal', 'receptionist', 'accountant', 'teacher'], true)
                ? $roleName
                : null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($staff->institute_id),
            'roles' => $this->roleOptions((int) $staff->institute_id),
        ]);
    }

    public function update(Request $request, Staff $staff): JsonResponse|RedirectResponse
    {
        $this->ensureCanManageStaff($staff, 'edit');
        $this->assertVisible($staff);

        $validated = $this->validateStaff($request, $staff);
        $validated['avatar'] = $request->file('avatar');
        $this->staffService->update($staff, $validated);

        if ($request->expectsJson()) {
            return Ajax::success('Staff updated.', $staff->fresh(['user.roles', 'branches']));
        }

        return redirect()
            ->route('admin.users', $this->usersTabQuery($staff->fresh()))
            ->with('success', 'Staff updated.');
    }

    public function destroy(Request $request, Staff $staff): JsonResponse|RedirectResponse
    {
        $this->ensureCanManageStaff($staff, 'delete');
        $this->assertVisible($staff);

        $this->staffService->delete($staff);

        if ($request->expectsJson()) {
            return Ajax::success('Staff deleted.');
        }

        return redirect()
            ->route('admin.users')
            ->with('success', 'Staff deleted.');
    }

    public function toggleStatus(Staff $staff): JsonResponse
    {
        $this->ensureCanManageStaff($staff, 'status');
        $this->assertVisible($staff);

        $updated = $this->staffService->toggleStatus($staff);

        return Ajax::success(
            $updated->status === 'active' ? 'Staff activated.' : 'Staff deactivated.',
            ['id' => $updated->id, 'status' => $updated->status]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateStaff(Request $request, ?Staff $staff = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        $branchIds = BranchScope::for()->allowedBranchIdsForInstitute($instituteId);

        $roleIds = $this->roleOptions($instituteId)->pluck('id')->all();

        $rules = [
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($staff?->user_id),
            ],
            'phone' => ['nullable', 'string', 'max:15'],
            'designation' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'expected_login_time' => ['nullable', 'date_format:H:i'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'joining_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'primary_branch_id' => ['required', 'integer', Rule::in($branchIds)],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($branchIds)],
            'role_id' => ['required', 'integer', Rule::in($roleIds)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];

        if ($staff) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules);
    }

    protected function instituteOptions()
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return Institute::query()->where('id', $user->institute_id)->get(['id', 'name']);
        }

        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function branchOptions(?int $instituteId = null)
    {
        $user = auth()->user();
        $instituteId ??= $user && ! $user->isPlatformUser() ? $user->institute_id : null;

        if (! $instituteId) {
            return collect();
        }

        return BranchScope::for()->branchesForInstitute($instituteId);
    }

    protected function roleOptions(?int $instituteId = null)
    {
        if (! $instituteId) {
            return collect();
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($instituteId);

        return Role::query()
            ->where('team_id', $instituteId)
            ->where('guard_name', 'web')
            ->where('status', 'active')
            ->whereNotIn('name', ['developer', 'institute_admin', 'teacher', 'branch_manager'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<string, string>
     */
    protected function usersTabQuery(Staff $staff): array
    {
        $role = strtolower((string) ($this->staffService->resolveRole($staff)['name'] ?? ''));

        if (! in_array($role, ['branch_manager', 'principal', 'receptionist', 'accountant', 'teacher'], true)) {
            return [];
        }

        return ['role' => $role];
    }

    protected function currentInstituteId(): ?int
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return (int) $user->institute_id;
        }

        return null;
    }

    protected function assertVisible(Staff $staff): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $staff->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        BranchScope::for($user)->assertSharesBranchWith($staff->user);
    }

    /**
     * `staff.add` still works as before; `users.add_{role}` (for principal/receptionist/accountant
     * only — branch_manager/teacher are created through their own dedicated flows) is an
     * additional, additive way to be granted the right to create that specific role.
     */
    protected function ensureCanAddStaff(string $roleName): void
    {
        $user = auth()->user();
        if ($user && $user->isPlatformUser()) {
            return;
        }

        if ($user && $user->can('staff.add')) {
            return;
        }

        if ($user && in_array($roleName, ['principal', 'receptionist', 'accountant'], true)
            && $user->can("users.add_{$roleName}")) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    /**
     * `staff.{action}` still works as before; the per-tab `users.{action}_{role}` permission
     * (from the merged Users list) is an additional, additive way to be granted the same action.
     */
    protected function ensureCanManageStaff(Staff $staff, string $action): void
    {
        $user = auth()->user();
        if ($user && $user->isPlatformUser()) {
            return;
        }

        if ($user && $user->can("staff.{$action}")) {
            return;
        }

        // `users.view_{tab}` doubles up as both "see in the list" and "view detail".
        $usersAction = $action === 'show' ? 'view' : $action;
        $tab = $this->staffService->roleTabFor($staff);
        if ($user && $tab !== 'other' && $user->can("users.{$usersAction}_{$tab}")) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
