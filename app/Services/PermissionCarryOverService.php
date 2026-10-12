<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionCarryOverService
{
    /**
     * Grant replacement permissions to every role (templates and institute copies)
     * that currently holds the legacy permission, so splitting a permission
     * never silently revokes existing access.
     *
     * @param  array<string, list<string>>  $map  legacy slug => replacement slugs
     * @return int number of role-permission rows inserted
     */
    public function carryOver(array $map): int
    {
        $relation = (new Role)->permissions();
        $table = $relation->getTable();
        $roleKey = $relation->getForeignPivotKeyName();
        $permKey = $relation->getRelatedPivotKeyName();

        $inserted = DB::transaction(function () use ($map, $table, $roleKey, $permKey) {
            $inserted = 0;

            foreach ($map as $legacySlug => $targetSlugs) {
                $legacy = Permission::query()->where('name', $legacySlug)->where('guard_name', 'web')->first();
                if (! $legacy) {
                    continue;
                }

                $roleIds = DB::table($table)->where($permKey, $legacy->getKey())->pluck($roleKey)->all();
                if ($roleIds === []) {
                    continue;
                }

                foreach ($targetSlugs as $slug) {
                    [$module, $action] = explode('.', $slug, 2);
                    $target = Permission::query()->firstOrCreate(
                        ['name' => $slug, 'guard_name' => 'web'],
                        ['module' => $module, 'action' => $action, 'is_active' => true]
                    );

                    $alreadyGranted = DB::table($table)
                        ->where($permKey, $target->getKey())
                        ->whereIn($roleKey, $roleIds)
                        ->pluck($roleKey)
                        ->all();

                    $rows = collect($roleIds)
                        ->diff($alreadyGranted)
                        ->map(fn ($roleId) => [$roleKey => $roleId, $permKey => $target->getKey()])
                        ->values()
                        ->all();

                    foreach (array_chunk($rows, 1000) as $chunk) {
                        DB::table($table)->insert($chunk);
                        $inserted += count($chunk);
                    }
                }
            }

            return $inserted;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $inserted;
    }
}
