<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
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
        $this->ensureCanAny(['teacher.view', 'staff.view']);

        $auth = auth()->user();
        $canTeacher = $auth && ($auth->isPlatformUser() || $auth->can('teacher.view'));
        $canStaff = $auth && ($auth->isPlatformUser() || $auth->can('staff.view'));

        $role = (string) $request->input('role', 'all');
        if ($role !== 'all' && ! in_array($role, self::ROLE_TABS, true)) {
            $role = 'all';
        }
        if ($role === 'teacher' && ! $canTeacher) {
            $role = 'all';
        }
        if ($role !== 'all' && $role !== 'teacher' && ! $canStaff) {
            $role = 'all';
        }

        $teachers = $canTeacher
            ? $this->scoped(Teacher::query(), $request)->with(['user', 'branches'])->get()
            : collect();
        $staff = $canStaff
            ? $this->scoped(Staff::query(), $request)->with(['user', 'branches'])->get()
            : collect();

        $roleNames = $this->staffService->roleNamesForStaff($staff);
        $rows = $this->mergeRows($teachers, $staff, $roleNames);

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
            if ($key === 'teacher' && ! $canTeacher) {
                continue;
            }
            if ($key !== 'all' && $key !== 'teacher' && ! $canStaff) {
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

        return view('admin.member.users', array_merge($filters, [
            'users' => $users,
            'tabs' => $tabs,
            'role' => $role,
            'roleLabel' => $labels[$role] ?? 'All',
            'canTeacher' => $canTeacher,
            'canStaff' => $canStaff,
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
     * @return Collection<int, array<string, mixed>>
     */
    protected function mergeRows(Collection $teachers, Collection $staff, array $roleNames): Collection
    {
        $byUser = [];

        foreach ($staff as $member) {
            if (! $member->user_id || ! $member->user) {
                continue;
            }

            $byUser[(int) $member->user_id] = $this->mapStaff($member, $roleNames);
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

            $byUser[$userId] = $this->mapTeacher($teacher);
        }

        return collect(array_values($byUser));
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapTeacher(Teacher $teacher): array
    {
        return $this->mapRow($teacher, 'teacher', 'Teacher', 'teacher');
    }

    /**
     * @param  array<string, string>  $roleNames
     * @return array<string, mixed>
     */
    protected function mapStaff(Staff $member, array $roleNames): array
    {
        $slug = strtolower((string) ($roleNames[$member->user_id.':'.$member->institute_id] ?? ''));
        if (! in_array($slug, self::ROLE_TABS, true)) {
            if ($member->user?->user_type === UserType::BranchManager
                || strcasecmp(trim((string) $member->designation), 'Branch Manager') === 0) {
                $slug = 'branch_manager';
            } else {
                $slug = 'other';
            }
        }

        $label = $slug === 'other'
            ? ($member->designation ?: 'Staff')
            : str_replace('_', ' ', $slug);
        $label = \Illuminate\Support\Str::headline($label);

        return $this->mapRow($member, $slug, $label, 'staff');
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapRow(Model $record, string $role, string $roleLabel, string $kind): array
    {
        $user = $record->user;
        $branches = $record->branches;
        $primary = $branches->first(fn ($branch) => (int) ($branch->pivot->is_primary ?? 0) === 1)
            ?? $branches->first();

        $showRoute = $kind === 'teacher' ? 'admin.master.teacher.show' : 'admin.staff.show';
        $editRoute = $kind === 'teacher' ? 'admin.master.teacher.edit' : 'admin.staff.edit';
        $toggleRoute = $kind === 'teacher' ? 'admin.master.teacher.toggle' : 'admin.staff.toggle';
        $destroyRoute = $kind === 'teacher' ? 'admin.master.teacher.destroy' : 'admin.staff.destroy';

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
        ];
    }
}
