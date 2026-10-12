<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionSyncService
{
    /**
     * @return array{created:int,updated:int,deactivated:int,assigned:int}
     */
    public function sync(): array
    {
        $matrix = config('permission_matrix');
        $defaults = $matrix['default_actions'] ?? [];
        $modules = $matrix['modules'] ?? [];
        $autoRoles = $matrix['auto_assign_roles'] ?? ['developer'];

        $created = 0;
        $updated = 0;
        $deactivated = 0;
        $sort = 0;
        $activeSlugs = [];
        /** @var array<string, list<int>> $createdByModule */
        $createdByModule = [];

        foreach ($modules as $moduleKey => $module) {
            $enabled = (bool) ($module['enabled'] ?? false);
            $actions = [];

            if ($module['use_defaults'] ?? false) {
                $actions = $defaults;
            }

            if (! empty($module['soft_delete']) && ($module['use_defaults'] ?? false)) {
                $actions['restore'] = 'Restore';
            }

            foreach ($module['actions'] ?? [] as $action => $label) {
                $actions[$action] = $label;
            }

            foreach ($actions as $action => $label) {
                $sort++;
                $slug = $moduleKey.'.'.$action;

                if (! $enabled) {
                    $existing = Permission::query()->where('name', $slug)->where('guard_name', 'web')->first();
                    if ($existing && $existing->is_active) {
                        $existing->is_active = false;
                        $existing->save();
                        $deactivated++;
                    }

                    continue;
                }

                $activeSlugs[] = $slug;

                $permission = Permission::query()->firstOrNew([
                    'name' => $slug,
                    'guard_name' => 'web',
                ]);

                $wasNew = ! $permission->exists;
                $permission->fill([
                    'module' => $moduleKey,
                    'action' => $action,
                    'description' => $label,
                    'is_active' => true,
                    'sort_order' => $sort,
                ]);
                $permission->save();

                if ($wasNew) {
                    $created++;
                    $createdByModule[$moduleKey][] = (int) $permission->id;
                } else {
                    $updated++;
                }
            }
        }

        $assigned = 0;
        $activePermissions = Permission::query()
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->whereIn('name', $activeSlugs ?: [''])
            ->get();

        // Deactivate matrix permissions that were removed / no longer generated.
        $matrixModules = array_keys($modules);
        if ($matrixModules !== []) {
            $deactivated += Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('module', $matrixModules)
                ->where('is_active', true)
                ->whereNotIn('name', $activeSlugs ?: [''])
                ->update(['is_active' => false]);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach ($autoRoles as $roleSlug) {
            $role = Role::query()->firstOrCreate(
                [
                    'name' => $roleSlug,
                    'guard_name' => 'web',
                    'team_id' => null,
                ],
                [
                    'is_system' => true,
                    'status' => 'active',
                ]
            );

            $role->syncPermissions($activePermissions);
            $assigned += $activePermissions->count();
        }

        $assigned += $this->grantToHolders($createdByModule, $modules, $autoRoles);

        // Platform role templates (developer = top parent) + push to institutes
        $templateResult = app(RoleTemplateService::class)->syncTemplatesAndPropagate(
            array_merge([], ...array_values($createdByModule))
        );
        $assigned += (int) ($templateResult['propagated'] ?? 0);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return compact('created', 'updated', 'deactivated', 'assigned');
    }

    /**
     * Give newly created actions of modules with `inherit_from` to the custom roles that already hold
     * that permission, so adding finer permissions never hides something those roles could see before.
     * Template roles get new permissions through their defaults instead (RoleTemplateService).
     *
     * @param  array<string, list<int>>  $createdByModule
     * @param  array<string, array<string, mixed>>  $modules
     * @param  list<string>  $autoRoles
     */
    private function grantToHolders(array $createdByModule, array $modules, array $autoRoles): int
    {
        $relation = (new Role)->permissions();
        $table = $relation->getTable();
        $roleKey = $relation->getForeignPivotKeyName();
        $permKey = $relation->getRelatedPivotKeyName();
        $granted = 0;

        foreach ($createdByModule as $moduleKey => $permissionIds) {
            $holder = $modules[$moduleKey]['inherit_from'] ?? null;
            $holderId = $holder
                ? Permission::query()->where('name', $holder)->where('guard_name', 'web')->value('id')
                : null;

            if (! $holderId) {
                continue;
            }

            $roleIds = Role::query()
                ->whereIn('id', DB::table($table)->where($permKey, $holderId)->select($roleKey))
                ->whereNotIn('name', array_merge(RoleTemplateService::TEMPLATE_SLUGS, $autoRoles))
                ->pluck('id');

            $rows = $roleIds->flatMap(fn ($roleId) => array_map(
                fn (int $permissionId) => [$roleKey => (int) $roleId, $permKey => $permissionId],
                $permissionIds
            ));

            foreach ($rows->chunk(1000) as $chunk) {
                $granted += DB::table($table)->insertOrIgnore($chunk->values()->all());
            }
        }

        return $granted;
    }
}
