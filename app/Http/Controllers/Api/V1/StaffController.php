<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\StaffResource;
use App\Http\Resources\Api\V1\TodoResource;
use App\Models\LoginLog;
use App\Models\Role;
use App\Models\Staff;
use App\Services\StaffAttendanceService;
use App\Services\StaffService;
use App\Services\TodoService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class StaffController extends ApiController
{
    public function __construct(
        protected StaffAttendanceService $attendanceService,
        protected TodoService $todoService,
        protected StaffService $staffService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCanAny(['staff.view', 'users.view_branch_manager', 'users.view_principal', 'users.view_receptionist', 'users.view_accountant']);

        $query = BranchScope::for($this->actor())
            ->applyToBranchMembers(Staff::query())
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['user:id,name,email,phone,avatar_path,user_type']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('employee_code', 'like', "%{$q}%")
                    ->orWhere('designation', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%"));
            });
        }

        $paginator = $query->orderByDesc('id')->paginate($this->perPage());
        $paginator->getCollection()->transform(function (Staff $staff) use ($request) {
            $staff->user?->load('roles');

            return (new StaffResource($staff))->resolve($request);
        });

        return $this->paginated('OK', $paginator);
    }

    public function show(Staff $staff): JsonResponse
    {
        $this->ensureCanAny(['staff.show', 'users.view_branch_manager', 'users.view_principal']);
        $this->assertVisible($staff);

        $staff->load(['user.roles', 'branches:id,name']);

        return Ajax::success('OK', (new StaffResource($staff))->resolve());
    }

    public function attendance(Request $request, Staff $staff): JsonResponse
    {
        $this->ensureCanAny(['staff.tab_attendance', 'teacher_attendance.view', 'teacher_attendance.show', 'staff.show', 'staff.view']);
        $this->assertVisible($staff);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $branchId = $staff->primaryBranch()?->id;

        return Ajax::success('Attendance loaded.', $this->attendanceService->userMonthCalendar(
            (int) $staff->user_id,
            (int) $staff->institute_id,
            $branchId ? (int) $branchId : null,
            (int) $validated['year'],
            (int) $validated['month']
        ));
    }

    public function loginHistory(Staff $staff): JsonResponse
    {
        $this->ensureCanAny(['staff.tab_login_history', 'staff.show', 'staff.view']);
        $this->assertVisible($staff);

        $logs = LoginLog::query()
            ->where('user_id', $staff->user_id)
            ->orderByDesc('logged_at')
            ->orderByDesc('id')
            ->paginate($this->perPage());

        $logs->getCollection()->transform(fn (LoginLog $log) => [
            'id' => $log->id,
            'event' => $log->event,
            'portal' => $log->portal,
            'ip' => $log->ip,
            'logged_at' => $log->logged_at,
        ]);

        return $this->paginated('OK', $logs);
    }

    public function assignedWork(Request $request, Staff $staff): JsonResponse
    {
        $this->ensureCanAny(['staff.tab_assigned_work', 'staff.show', 'staff.view']);
        $this->assertVisible($staff);

        $rows = $this->todoService->paginateForAssignee(
            (int) $staff->user_id,
            (int) $staff->institute_id,
            $this->perPage(),
            'page'
        );
        $rows->getCollection()->transform(
            fn ($todo) => (new TodoResource($todo))->resolve($request)
        );

        return $this->paginated('OK', $rows);
    }

    public function roles(): JsonResponse
    {
        $this->ensureCanAny([
            'staff.add',
            'staff.edit',
            'users.add_principal',
            'users.add_receptionist',
            'users.add_accountant',
            'users.edit_principal',
            'users.edit_receptionist',
            'users.edit_accountant',
        ]);

        $instituteId = (int) $this->actor()->institute_id;
        $this->staffService->ensureDefaultRoles($instituteId);

        $items = $this->roleOptions($instituteId)->map(fn (Role $role) => [
            'id' => $role->id,
            'name' => $role->name,
        ])->values()->all();

        return Ajax::success('OK', ['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCanAny([
            'staff.add',
            'users.add_principal',
            'users.add_receptionist',
            'users.add_accountant',
        ]);

        $validated = $this->validateStaff($request);
        $roleName = ! empty($validated['role_id'])
            ? strtolower((string) Role::query()->find($validated['role_id'])?->name)
            : '';
        $this->ensureCanAddStaff($roleName);

        if ($limitError = $this->staffService->staffLimitError((int) $validated['institute_id'])) {
            return Ajax::error($limitError, null, 403);
        }

        $validated['avatar'] = $request->file('avatar');
        $staff = $this->staffService->create($validated);

        return Ajax::success(
            'Staff created.',
            (new StaffResource($staff->load(['user.roles', 'branches:id,name'])))->resolve(),
            201
        );
    }

    public function update(Request $request, Staff $staff): JsonResponse
    {
        $this->ensureCanManageStaff($staff, 'edit');
        $this->assertVisible($staff);

        $validated = $this->validateStaff($request, $staff);
        $validated['avatar'] = $request->file('avatar');
        $this->staffService->update($staff, $validated);

        return Ajax::success(
            'Staff updated.',
            (new StaffResource($staff->fresh(['user.roles', 'branches:id,name'])))->resolve()
        );
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $this->ensureCanManageStaff($staff, 'delete');
        $this->assertVisible($staff);
        $this->staffService->delete($staff);

        return Ajax::success('Staff deleted.');
    }

    public function status(Staff $staff): JsonResponse
    {
        $this->ensureCanManageStaff($staff, 'status');
        $this->assertVisible($staff);

        $updated = $this->staffService->toggleStatus($staff);

        return Ajax::success(
            $updated->status === 'active' ? 'Staff activated.' : 'Staff deactivated.',
            (new StaffResource($updated->load(['user.roles'])))->resolve()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateStaff(Request $request, ?Staff $staff = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);
        $branchIds = BranchScope::for($this->actor())->allowedBranchIdsForInstitute($instituteId);
        $this->staffService->ensureDefaultRoles($instituteId);
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
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'designation' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'expected_login_time' => ['nullable', 'date_format:H:i'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'joining_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'primary_branch_id' => ['required', 'integer', Rule::in($branchIds ?: [0])],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($branchIds ?: [0])],
            'role_id' => ['required', 'integer', Rule::in($roleIds ?: [0])],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];

        $rules['password'] = $staff
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate($rules);
    }

    protected function roleOptions(int $instituteId)
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($instituteId);

        return Role::query()
            ->where('team_id', $instituteId)
            ->where('guard_name', 'web')
            ->where('status', 'active')
            ->whereNotIn('name', ['developer', 'institute_admin', 'teacher', 'branch_manager'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function ensureCanAddStaff(string $roleName): void
    {
        $user = $this->actor();
        if ($user->can('staff.add')) {
            return;
        }

        if (in_array($roleName, ['principal', 'receptionist', 'accountant'], true)
            && $user->can("users.add_{$roleName}")) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    protected function ensureCanManageStaff(Staff $staff, string $action): void
    {
        $user = $this->actor();
        if ($user->can("staff.{$action}")) {
            return;
        }

        $usersAction = $action === 'show' ? 'view' : $action;
        $tab = $this->staffService->roleTabFor($staff);
        if ($tab !== 'other' && $user->can("users.{$usersAction}_{$tab}")) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    protected function assertVisible(Staff $staff): void
    {
        if ((int) $staff->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Staff not found.');
        }

        BranchScope::for($this->actor())->assertSharesBranchWith($staff->user);
    }
}
