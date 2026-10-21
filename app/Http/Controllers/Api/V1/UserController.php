<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use App\Services\StaffService;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Support\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class UserController extends ApiController
{
    public const TABS = [
        'teacher',
        'branch_manager',
        'principal',
        'receptionist',
        'accountant',
    ];

    public function __construct(
        protected StaffService $staffService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tab' => ['required', Rule::in(self::TABS)],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'status' => ['nullable', 'in:active,inactive'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $tab = $validated['tab'];
        $this->ensureCan('users.view_'.$tab);

        $scope = BranchScope::for($this->actor());
        if (! empty($validated['branch_id'])) {
            $scope->assertCanAccessBranch((int) $validated['branch_id']);
        }

        $rows = $tab === 'teacher'
            ? $this->teacherRows($request)
            : $this->staffRows($request, $tab);

        $page = max(1, (int) $request->input('page', 1));
        $perPage = $this->perPage();
        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->paginated('OK', $paginator);
    }

    public function permissions(User $user): JsonResponse
    {
        $this->ensureCan('roles.assign_permissions');
        $this->assertSameTeam($user);

        return $this->withUserTeam($user, function () use ($user) {
            $moduleMeta = collect(config('permission_matrix.modules', []))
                ->mapWithKeys(fn ($m, $key) => [$key => $m['name'] ?? Str::headline($key)]);

            $rolePermissionIds = $user->getPermissionsViaRoles()
                ->pluck('id')->map(fn ($id) => (int) $id)->all();
            $directPermissionIds = $user->permissions()
                ->pluck('id')->map(fn ($id) => (int) $id)->all();
            $deniedPermissionIds = $user->deniedPermissions()
                ->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();

            $modules = Tenancy::permissionsGroupedByModule($user)
                ->map(function ($perms, $moduleKey) use ($moduleMeta, $rolePermissionIds, $directPermissionIds, $deniedPermissionIds) {
                    return [
                        'key' => $moduleKey,
                        'label' => $moduleMeta[$moduleKey] ?? Str::headline($moduleKey),
                        'permissions' => $perms->map(function ($perm) use ($rolePermissionIds, $directPermissionIds, $deniedPermissionIds) {
                            $id = (int) $perm->id;
                            $viaRole = in_array($id, $rolePermissionIds, true);
                            $denied = in_array($id, $deniedPermissionIds, true);
                            $checked = ! $denied && ($viaRole || in_array($id, $directPermissionIds, true));

                            return [
                                'id' => $id,
                                'label' => $perm->description ?: $perm->action,
                                'via_role' => $viaRole,
                                'checked' => $checked,
                            ];
                        })->values(),
                    ];
                })
                ->values();

            return Ajax::success('User permissions loaded.', [
                'user' => ['id' => $user->id, 'name' => $user->name],
                'modules' => $modules,
            ]);
        });
    }

    public function syncPermissions(Request $request, User $user): JsonResponse
    {
        $this->ensureCan('roles.assign_permissions');
        $this->assertSameTeam($user);

        $validated = $request->validate([
            'grant_ids' => ['array'],
            'grant_ids.*' => ['integer', 'exists:permissions,id'],
            'deny_ids' => ['array'],
            'deny_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        return $this->withUserTeam($user, function () use ($user, $validated) {
            $allowed = Tenancy::assignablePermissions($user)->pluck('id')->all();
            $grantIds = array_values(array_intersect($validated['grant_ids'] ?? [], $allowed));
            $denyIds = array_values(array_diff(
                array_intersect($validated['deny_ids'] ?? [], $allowed),
                $grantIds
            ));

            $user->syncPermissions($grantIds);

            $teamId = (int) (getPermissionsTeamId() ?? 0);
            DB::table('user_permission_denials')
                ->where('team_id', $teamId)
                ->where('user_id', $user->id)
                ->delete();

            if ($denyIds !== []) {
                DB::table('user_permission_denials')->insert(array_map(
                    fn (int $permissionId) => [
                        'team_id' => $teamId,
                        'user_id' => $user->id,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    $denyIds
                ));
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return Ajax::success('User permissions updated.', [
                'granted' => count($grantIds),
                'denied' => count($denyIds),
            ]);
        });
    }

    protected function assertSameTeam(User $user): void
    {
        if (! $user->institute_id || (int) $user->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'User not found.');
        }
    }

    protected function withUserTeam(User $user, callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($user->institute_id ?: (int) config('tenancy.platform_team_id', 0));

        try {
            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function teacherRows(Request $request)
    {
        $query = BranchScope::for($this->actor())
            ->applyToBranchMembers(Teacher::query())
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['user:id,name,email,phone,avatar_path,is_active', 'branches:id,name']);

        $this->applyCommonFilters($query, $request);

        return $query->orderByDesc('id')->get()->map(fn (Teacher $teacher) => $this->mapMember($teacher, 'teacher', 'Teacher'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function staffRows(Request $request, string $tab)
    {
        $query = BranchScope::for($this->actor())
            ->applyToBranchMembers(Staff::query())
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['user:id,name,email,phone,avatar_path,is_active,user_type', 'branches:id,name']);

        $this->applyCommonFilters($query, $request);

        return $query->orderByDesc('id')->get()
            ->filter(fn (Staff $staff) => $this->staffService->roleTabFor($staff) === $tab)
            ->map(fn (Staff $staff) => $this->mapMember($staff, $tab, str_replace('_', ' ', $tab)))
            ->values();
    }

    protected function applyCommonFilters($query, Request $request): void
    {
        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            $query->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('employee_code', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"));
            });
        }
    }

    /**
     * @param  Teacher|Staff  $record
     * @return array<string, mixed>
     */
    protected function mapMember($record, string $role, string $roleLabel): array
    {
        $user = $record->user;
        $primary = $record->relationLoaded('branches')
            ? ($record->branches->first(fn ($b) => (int) ($b->pivot->is_primary ?? 0) === 1) ?? $record->branches->first())
            : null;

        return [
            'id' => $record->id,
            'user_id' => (int) $record->user_id,
            'kind' => $record instanceof Teacher ? 'teacher' : 'staff',
            'role' => $role,
            'role_label' => ucwords($roleLabel),
            'employee_code' => $record->employee_code,
            'status' => $record->status,
            'name' => $user?->name,
            'email' => $user?->email,
            'phone' => $user?->phone,
            'avatar_url' => $user?->avatar_path ? asset('storage/'.$user->avatar_path) : null,
            'branch' => $primary ? ['id' => $primary->id, 'name' => $primary->name] : null,
        ];
    }
}
