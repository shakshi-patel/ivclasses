<?php

namespace App\Http\Controllers\Admin;

use App\Models\Staff;
use App\Models\Teacher;
use App\Services\StaffService;
use App\Support\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MemberUserController extends AdminController
{
    /** @var list<string> */
    public const ROLE_TABS = [
        'branch_manager',
        'teacher',
        'principal',
        'receptionist',
        'accountant',
    ];

    public function __construct(protected StaffService $staffService) {}

    public function index(Request $request): View
    {
        $auth = auth()->user();
        $can = fn (string $perm) => $auth && ($auth->isPlatformUser() || $auth->can($perm));

        // Visibility for each "Users" tab is driven by its own `users.view_{tab}` permission.
        // `staff.view` only remains as the fallback for staff whose designation doesn't match
        // any of the four named tabs (the 'other' bucket has no dedicated permission of its own).
        $allowedRoles = [
            'teacher' => $can('users.view_teacher'),
            'branch_manager' => $can('users.view_branch_manager'),
            'principal' => $can('users.view_principal'),
            'receptionist' => $can('users.view_receptionist'),
            'accountant' => $can('users.view_accountant'),
            'other' => $can('staff.view'),
        ];

        $this->ensureCanAny([
            'users.view_teacher', 'users.view_branch_manager', 'users.view_principal',
            'users.view_receptionist', 'users.view_accountant', 'staff.view',
        ]);

        $canTeacher = $allowedRoles['teacher'];
        $canAnyStaffRole = $allowedRoles['branch_manager'] || $allowedRoles['principal']
            || $allowedRoles['receptionist'] || $allowedRoles['accountant'] || $allowedRoles['other'];

        $role = (string) $request->input('role', 'all');
        if ($role !== 'all' && ! in_array($role, self::ROLE_TABS, true)) {
            $role = 'all';
        }
        if ($role !== 'all' && empty($allowedRoles[$role])) {
            $role = 'all';
        }

        $teachers = $canTeacher
            ? $this->scoped(Teacher::query(), $request)->with(['user', 'branches'])->get()
            : collect();
        $staff = $canAnyStaffRole
            ? $this->scoped(Staff::query(), $request)->with(['user', 'branches'])->get()
            : collect();

        $roleNames = $this->staffService->roleNamesForStaff($staff);
        $rows = $this->mergeRows($teachers, $staff, $roleNames, $can)
            ->filter(fn (array $row) => ! empty($allowedRoles[$row['role']]))
            ->values();

        $labels = [
            'all' => 'All',
            'branch_manager' => 'Branch Manager',
            'teacher' => 'Teacher',
            'principal' => 'Principal',
            'receptionist' => 'Receptionist',
            'accountant' => 'Accountant',
        ];

        $tabs = [];
        foreach ($labels as $key => $label) {
            if ($key !== 'all' && empty($allowedRoles[$key])) {
                continue;
            }

            $tabs[] = [
                'key' => $key,
                'label' => $label,
                'count' => $key === 'all'
                    ? $rows->count()
                    : $rows->where('role', $key)->count(),
                'active' => $role === $key,
            ];
        }

        $users = $rows
            ->sortBy(fn (array $row) => strtolower($row['name']))
            ->values();

        $filters = $this->listFilterVars($request, ['branches']);

        $addLinks = [];
        foreach ([
            'teacher' => ['label' => 'Teacher', 'perm' => 'users.add_teacher', 'url' => route('admin.master.teacher.add')],
            'principal' => ['label' => 'Principal', 'perm' => 'users.add_principal', 'url' => route('admin.staff.add', ['role' => 'principal'])],
            'receptionist' => ['label' => 'Receptionist', 'perm' => 'users.add_receptionist', 'url' => route('admin.staff.add', ['role' => 'receptionist'])],
            'accountant' => ['label' => 'Accountant', 'perm' => 'users.add_accountant', 'url' => route('admin.staff.add', ['role' => 'accountant'])],
            // Creating a branch manager happens via the Branch form — there is no standalone
            // "add branch manager" flow, so either permission unlocks that same link.
            'branch_manager' => ['label' => 'Branch Manager', 'perm' => 'users.add_branch_manager', 'url' => route('admin.master.branch.add'), 'altPerm' => 'branch.add'],
        ] as $key => $link) {
            if ($can($link['perm']) || ($link['altPerm'] ?? null) && $can($link['altPerm'])) {
                $addLinks[$key] = ['label' => $link['label'], 'url' => $link['url']];
            }
        }

        return view('admin.member.users', array_merge($filters, [
            'users' => $users,
            'tabs' => $tabs,
            'role' => $role,
            'roleLabel' => $labels[$role] ?? 'All',
            'canTeacher' => $canTeacher,
            'canStaff' => $canAnyStaffRole,
            'addLinks' => $addLinks,
        ]));
    }

    /**
     * @param  Builder<Teacher>|Builder<Staff>  $query
     * @return Builder<Teacher>|Builder<Staff>
     */
    protected function scoped(Builder $query, Request $request): Builder
    {
        $query = BranchScope::for()->applyToBranchMembers($query);
        $this->applyInstituteFilter($query, $request);

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            $query->whereHas('branches', fn (Builder $q) => $q->where('branches.id', $branchId));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }

    /**
     * @param  Collection<int, Teacher>  $teachers
     * @param  Collection<int, Staff>  $staff
     * @param  array<string, string>  $roleNames
     * @param  callable(string): bool  $can
     * @return Collection<int, array<string, mixed>>
     */
    protected function mergeRows(Collection $teachers, Collection $staff, array $roleNames, callable $can): Collection
    {
        $byUser = [];

        foreach ($staff as $member) {
            if (! $member->user_id || ! $member->user) {
                continue;
            }

            $byUser[(int) $member->user_id] = $this->mapStaff($member, $can);
        }

        foreach ($teachers as $teacher) {
            if (! $teacher->user_id || ! $teacher->user) {
                continue;
            }

            $userId = (int) $teacher->user_id;
            $existing = $byUser[$userId]['role'] ?? null;
            if (in_array($existing, ['branch_manager', 'principal', 'receptionist', 'accountant'], true)) {
                continue;
            }

            $byUser[$userId] = $this->mapTeacher($teacher, $can);
        }

        return collect(array_values($byUser));
    }

    /**
     * @param  callable(string): bool  $can
     * @return array<string, mixed>
     */
    protected function mapTeacher(Teacher $teacher, callable $can): array
    {
        return $this->mapRow($teacher, 'teacher', 'Teacher', 'teacher', $can);
    }

    /**
     * @param  callable(string): bool  $can
     * @return array<string, mixed>
     */
    protected function mapStaff(Staff $member, callable $can): array
    {
        $slug = $this->staffService->roleTabFor($member);

        $label = $slug === 'other'
            ? ($member->designation ?: 'Staff')
            : str_replace('_', ' ', $slug);
        $label = \Illuminate\Support\Str::headline($label);

        return $this->mapRow($member, $slug, $label, 'staff', $can);
    }

    /**
     * @param  callable(string): bool  $can
     * @return array<string, mixed>
     */
    protected function mapRow(Model $record, string $role, string $roleLabel, string $kind, callable $can): array
    {
        $user = $record->user;
        $branches = $record->branches;
        $primary = $branches->first(fn ($branch) => (int) ($branch->pivot->is_primary ?? 0) === 1)
            ?? $branches->first();

        $showRoute = $kind === 'teacher' ? 'admin.master.teacher.show' : 'admin.staff.show';
        $editRoute = $kind === 'teacher' ? 'admin.master.teacher.edit' : 'admin.staff.edit';
        $toggleRoute = $kind === 'teacher' ? 'admin.master.teacher.toggle' : 'admin.staff.toggle';
        $destroyRoute = $kind === 'teacher' ? 'admin.master.teacher.destroy' : 'admin.staff.destroy';

        // 'other' (unclassified staff, no dedicated tab) falls back to the generic staff.* actions.
        if ($role === 'other') {
            $canShow = $can('staff.show');
            $canEdit = $can('staff.edit');
            $canStatus = $can('staff.status');
            $canDelete = $can('staff.delete');
        } else {
            $canShow = $can("users.view_{$role}");
            $canEdit = $can("users.edit_{$role}");
            $canStatus = $can("users.status_{$role}");
            $canDelete = $can("users.delete_{$role}");
        }

        return [
            'id' => $record->id,
            'user_id' => (int) $record->user_id,
            'kind' => $kind,
            'role' => $role,
            'role_label' => $roleLabel,
            'name' => $user?->name ?: '—',
            'code' => $record->employee_code ?: null,
            'email' => $user?->email ?: '—',
            'phone' => $user?->phone ?: '—',
            'branch' => $primary?->name ?: '—',
            'active' => $record->isActive(),
            'avatar' => $user?->avatar_path
                ? asset('storage/'.$user->avatar_path)
                : asset('admin-assets/img/avatars/1.png'),
            'show_url' => route($showRoute, $record),
            'edit_url' => route($editRoute, $record),
            'toggle_url' => route($toggleRoute, $record),
            'destroy_url' => route($destroyRoute, $record),
            'can_show' => $canShow,
            'can_edit' => $canEdit,
            'can_status' => $canStatus,
            'can_delete' => $canDelete,
        ];
    }
}
