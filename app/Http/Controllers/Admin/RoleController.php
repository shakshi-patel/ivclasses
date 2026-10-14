<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Support\Ajax;
use App\Support\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends AdminController
{
    public function index(): View
    {
        $this->ensureCan('roles.view');

        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            app(\App\Services\RoleTemplateService::class)
                ->ensureInstituteRolesFromTemplates((int) $user->institute_id);
        }

        $roles = Tenancy::rolesQuery()
            ->with(['parentRole', 'assignableRoles'])
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        $ownRoleIds = $user
            ? $user->roles->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        if ($ownRoleIds !== []) {
            $roles = $roles->reject(fn (Role $role) => in_array((int) $role->id, $ownRoleIds, true))->values();
        }

        $parentOptions = Tenancy::rolesQuery()
            ->when($ownRoleIds !== [], fn ($q) => $q->whereNotIn('id', $ownRoleIds))
            ->orderBy('name')
            ->get(['id', 'name']);
        $assignableOptions = $parentOptions;
        $permissionModules = Tenancy::permissionsGroupedByModule();
        $moduleMeta = collect(config('permission_matrix.modules', []))
            ->mapWithKeys(fn ($m, $key) => [$key => $m['name'] ?? Str::headline($key)]);

        return view('admin.master.roles.roles', compact(
            'roles',
            'parentOptions',
            'assignableOptions',
            'permissionModules',
            'moduleMeta'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('roles.add');

        $validated = $this->validateRole($request, null);

        $teamId = Tenancy::teamId();
        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'team_id' => Tenancy::isPlatformTeam() ? null : $teamId,
            'parent_role_id' => $validated['parent_role_id'] ?? null,
            'status' => $validated['status'],
            'is_system' => false,
            'image_path' => $this->storeImage($request),
        ]);

        $role->assignableRoles()->sync($validated['assignable_role_ids'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return Ajax::success('Role created.', $role, 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->ensureCan('roles.edit');
        $this->assertTeamRole($role);

        if ($role->is_system && $role->name === 'developer') {
            return Ajax::error('Developer role cannot be edited.', null, 403);
        }

        $validated = $this->validateRole($request, $role);

        $role->fill([
            'name' => $validated['name'],
            'parent_role_id' => $validated['parent_role_id'] ?? null,
            'status' => $validated['status'],
        ]);

        if ($request->hasFile('image')) {
            if ($role->image_path) {
                Storage::disk('public')->delete($role->image_path);
            }
            $role->image_path = $this->storeImage($request);
        }

        $role->save();
        $role->assignableRoles()->sync($validated['assignable_role_ids'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return Ajax::success('Role updated.', $role->fresh(['assignableRoles', 'parentRole']));
    }

    public function destroy(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $this->ensureCan('roles.delete');
        $this->assertTeamRole($role);

        if ($role->is_system) {
            return $this->respondDestroyFailed($request, 'System roles cannot be deleted.', 403);
        }

        if ($role->image_path) {
            Storage::disk('public')->delete($role->image_path);
        }

        $role->assignableRoles()->detach();
        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->respondDestroyed($request, 'Role deleted.', 'admin.master.roles');
    }

    public function permissions(Role $role): JsonResponse
    {
        $this->ensureCan('roles.assign_permissions');
        $this->assertTeamRole($role);

        return Ajax::success('Role permissions loaded.', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
            'permission_ids' => $role->permissions()->pluck('id')->map(fn ($id) => (int) $id)->values(),
        ]);
    }

    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $this->ensureCan('roles.assign_permissions');
        $this->assertTeamRole($role);

        if ($role->name === 'developer' && $role->is_system) {
            return Ajax::error('Developer permissions are managed by matrix sync.', null, 403);
        }

        $validated = $request->validate([
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $allowed = Tenancy::assignablePermissions()->pluck('id')->all();
        $ids = array_values(array_intersect($validated['permission_ids'] ?? [], $allowed));

        $role->syncPermissions($ids);

        // Platform template edit → push to all institute copies (bulk, not N× Spatie sync)
        $templateService = app(\App\Services\RoleTemplateService::class);
        if ($templateService->isPlatformTemplate($role)) {
            $templateService->syncPermissionsToInstituteCopies($role, $ids);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return Ajax::success('Permissions saved.', [
            'count' => count($ids),
        ]);
    }

    /**
     * @return array{name:string,status:string,parent_role_id?:int|null,assignable_role_ids?:array<int>}
     */
    protected function validateRole(Request $request, ?Role $role): array
    {
        $teamRoleIds = Tenancy::rolesQuery()->pluck('id')->all();
        if ($role) {
            $teamRoleIds = array_values(array_filter(
                $teamRoleIds,
                fn ($id) => (int) $id !== (int) $role->id
            ));
        }

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail) use ($role) {
                    $exists = Tenancy::rolesQuery()
                        ->where('guard_name', 'web')
                        ->where('name', $value)
                        ->when($role, fn ($q) => $q->where('id', '!=', $role->id))
                        ->exists();
                    if ($exists) {
                        $fail('A role with this name already exists.');
                    }
                },
            ],
            'status' => ['required', 'in:active,inactive'],
            'parent_role_id' => ['nullable', 'integer', Rule::in($teamRoleIds)],
            'assignable_role_ids' => ['nullable', 'array'],
            'assignable_role_ids.*' => ['integer', Rule::in(
                Tenancy::rolesQuery()->pluck('id')->all()
            )],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    protected function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('roles', 'public');
    }

    protected function assertTeamRole(Role $role): void
    {
        $teamId = Tenancy::teamId();
        $roleTeam = $role->team_id;

        $ok = Tenancy::isPlatformTeam()
            ? ($roleTeam === null || (int) $roleTeam === 0)
            : (int) $roleTeam === (int) $teamId;

        if (! $ok) {
            abort(404);
        }

        $user = auth()->user();
        if ($user && $user->roles->contains('id', $role->id)) {
            abort(403, 'You cannot manage your own role.');
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
