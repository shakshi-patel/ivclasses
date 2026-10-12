<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Support\Ajax;
use App\Support\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class UserPermissionController extends AdminController
{
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
        $auth = auth()->user();
        if ($auth && $auth->isPlatformUser()) {
            return;
        }

        if (! $auth || ! $user->institute_id || (int) $user->institute_id !== (int) $auth->institute_id) {
            abort(404);
        }
    }

    /**
     * Spatie's team scoping follows the viewing session's ambient team id, which is
     * the platform (0) for a platform admin — but the target user's roles/permissions
     * live under their own institute's team id. Switch to it for the duration of the
     * callback so reads/writes land on (and read from) the correct team, then restore.
     */
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
}
