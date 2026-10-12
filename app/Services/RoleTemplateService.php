<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Tenancy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Platform (team_id null) role templates — developer is top parent.
 * Institute roles are cloned/synced from these templates.
 */
class RoleTemplateService
{
    /** @var list<string> */
    public const TEMPLATE_SLUGS = [
        'institute_admin',
        'principal',
        'accountant',
        'receptionist',
        'teacher',
        'branch_manager',
        'parent',
    ];

    /**
     * Ensure platform templates exist, wire parent hierarchy, seed default perms if empty,
     * then push template permissions to every institute copy of those roles.
     *
     * @param  list<int>  $newPermissionIds  permissions created by this sync; templates get the ones in their defaults
     * @return array{templates:int,propagated:int}
     */
    public function syncTemplatesAndPropagate(array $newPermissionIds = []): array
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $developer = Role::query()->firstOrCreate(
            [
                'name' => 'developer',
                'guard_name' => 'web',
                'team_id' => null,
            ],
            [
                'is_system' => true,
                'status' => 'active',
            ]
        );

        $templates = 0;
        $bySlug = [];

        foreach (self::TEMPLATE_SLUGS as $slug) {
            $role = Role::query()->firstOrCreate(
                [
                    'name' => $slug,
                    'guard_name' => 'web',
                    'team_id' => null,
                ],
                [
                    'is_system' => true,
                    'status' => 'active',
                    'parent_role_id' => $slug === 'institute_admin'
                        ? $developer->id
                        : null,
                ]
            );

            if ($slug === 'institute_admin') {
                $role->parent_role_id = $developer->id;
            }

            $bySlug[$slug] = $role;
            $templates++;
        }

        $adminTemplate = $bySlug['institute_admin'];
        foreach ($bySlug as $slug => $role) {
            if ($slug === 'institute_admin') {
                continue;
            }
            $role->parent_role_id = $adminTemplate->id;
            $role->save();
        }
        $adminTemplate->save();

        // Assignable: institute_admin may assign child staff roles
        $assignableIds = collect($bySlug)
            ->except('institute_admin')
            ->pluck('id')
            ->all();
        $adminTemplate->assignableRoles()->sync($assignableIds);

        foreach ($bySlug as $slug => $role) {
            $this->ensureDefaultPermissions($role, $slug, $newPermissionIds);
        }

        $propagated = $this->propagateTemplatesToInstitutes();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [
            'templates' => $templates,
            'propagated' => $propagated,
        ];
    }

    /**
     * Create/update institute roles from platform templates (permissions + parent links within team).
     */
    public function ensureInstituteRolesFromTemplates(int $instituteId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($instituteId);

        $templates = $this->platformTemplates()->keyBy('name');
        if ($templates->isEmpty()) {
            $this->syncTemplatesAndPropagate();
            $templates = $this->platformTemplates()->keyBy('name');
        }

        $created = [];
        foreach (self::TEMPLATE_SLUGS as $slug) {
            /** @var Role|null $template */
            $template = $templates->get($slug);
            $role = Role::query()->firstOrCreate(
                [
                    'name' => $slug,
                    'guard_name' => 'web',
                    'team_id' => $instituteId,
                ],
                [
                    'is_system' => true,
                    'status' => 'active',
                ]
            );
            $created[$slug] = $role;

            // Seed from template only when institute role has no permissions yet
            if ($template && ! $role->permissions()->exists()) {
                $role->syncPermissions($template->permissions);
            }
        }

        // Intra-institute parent hierarchy (mirror templates)
        $admin = $created['institute_admin'] ?? null;
        if ($admin) {
            foreach ($created as $slug => $role) {
                if ($slug === 'institute_admin') {
                    $role->parent_role_id = null;
                } else {
                    $role->parent_role_id = $admin->id;
                }
                $role->save();
            }

            $assignableIds = collect($created)
                ->except('institute_admin')
                ->pluck('id')
                ->all();
            $admin->assignableRoles()->sync($assignableIds);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return Collection<int, Role>
     */
    public function platformTemplates(): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereNull('team_id')
            ->whereIn('name', self::TEMPLATE_SLUGS)
            ->with('permissions')
            ->get();
    }

    public function isPlatformTemplate(Role $role): bool
    {
        return ($role->team_id === null || (int) $role->team_id === 0)
            && in_array($role->name, self::TEMPLATE_SLUGS, true);
    }

    /**
     * Push a platform template's permission set to every institute copy of that role.
     * Uses bulk pivot writes (not N× Spatie syncPermissions).
     *
     * @param  list<int|string>  $permissionIds
     */
    public function syncPermissionsToInstituteCopies(Role $template, array $permissionIds): int
    {
        $copyIds = Role::query()
            ->where('name', $template->name)
            ->where('guard_name', 'web')
            ->whereNotNull('team_id')
            ->where('team_id', '>', 0)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($copyIds === []) {
            return 0;
        }

        $this->bulkReplaceRolePermissions($copyIds, $permissionIds);

        return count($copyIds);
    }

    /**
     * Replace role↔permission pivots for many roles in a few queries.
     * Table / column names come from Spatie's Role::permissions() relation (not hardcoded).
     *
     * @param  list<int>  $roleIds
     * @param  list<int|string>  $permissionIds
     */
    public function bulkReplaceRolePermissions(array $roleIds, array $permissionIds): void
    {
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        if ($roleIds === []) {
            return;
        }

        $relation = (new Role)->permissions();
        $table = $relation->getTable();
        $roleKey = $relation->getForeignPivotKeyName();
        $permKey = $relation->getRelatedPivotKeyName();

        DB::transaction(function () use ($table, $roleKey, $permKey, $roleIds, $permissionIds) {
            DB::table($table)->whereIn($roleKey, $roleIds)->delete();

            if ($permissionIds === []) {
                return;
            }

            $rows = [];
            foreach ($roleIds as $roleId) {
                foreach ($permissionIds as $permissionId) {
                    $rows[] = [
                        $roleKey => $roleId,
                        $permKey => $permissionId,
                    ];

                    if (count($rows) >= 1000) {
                        DB::table($table)->insert($rows);
                        $rows = [];
                    }
                }
            }

            if ($rows !== []) {
                DB::table($table)->insert($rows);
            }
        });
    }

    /**
     * @param  list<int>  $newPermissionIds
     */
    protected function ensureDefaultPermissions(Role $role, string $slug, array $newPermissionIds = []): void
    {
        // Never wipe custom template edits — only seed when empty.
        if ($role->permissions()->exists()) {
            // institute_admin template: add any newly synced CRM permissions
            if ($slug === 'institute_admin') {
                $existing = $role->permissions()->pluck('id')->all();
                $all = Tenancy::institutePermissions()->pluck('id')->all();
                $merged = array_values(array_unique(array_merge($existing, $all)));
                $role->syncPermissions($merged);

                return;
            }

            // Newly introduced permissions that belong to this role's defaults (removed ones stay removed).
            $added = array_values(array_intersect($this->defaultPermissionIdsFor($slug), $newPermissionIds));
            if ($added !== []) {
                $role->givePermissionTo($added);
            }

            return;
        }

        $role->syncPermissions($this->defaultPermissionIdsFor($slug));
    }

    /**
     * Every section of a dashboard permission module (e.g. teacher_dashboard.*).
     *
     * @return list<string>
     */
    protected function dashboardSections(string $module): array
    {
        return array_map(
            fn (string $action) => $module.'.'.$action,
            array_keys(config("permission_matrix.modules.{$module}.actions", []))
        );
    }

    /**
     * @return list<int>
     */
    protected function defaultPermissionIdsFor(string $slug): array
    {
        $names = match ($slug) {
            'institute_admin' => Tenancy::institutePermissions()->pluck('name')->all(),
            'teacher' => [
                'dashboard.view',
                ...$this->dashboardSections('teacher_dashboard'),
                'batch.view',
            ],
            'parent' => [
                'dashboard.view',
                ...$this->dashboardSections('parent_dashboard'),
                'homework.view',
                'homework.show',
                'homework.download',
                'study_material.view',
                'study_material.show',
                'study_material.download',
                'notice.view',
                'notice.show',
                'exam.view',
                'exam.show',
                'exam.download',
                'result_announcement.view',
                'result_announcement.show',
                'result_announcement.export',
                'student_leave.show',
                'student_leave.add',
            ],
            'accountant' => [
                'dashboard.view',
                ...$this->dashboardSections('institute_dashboard'),
                'fees.view',
                'fees.show',
                'fees.add',
                'fees.receipt',
                'fees_follow_up.view',
                'fees_follow_up.show',
                'fees_follow_up.add',
                'gst_invoice.view',
                'gst_invoice.add',
                'income_expense.view',
                'income_expense.add',
                'income_expense.edit',
                'student.view',
            ],
            'receptionist' => [
                'dashboard.view',
                ...$this->dashboardSections('institute_dashboard'),
                'inquiry.view',
                'inquiry.show',
                'inquiry.receipt',
                'inquiry.add',
                'inquiry.edit',
                'follow_up.view',
                'follow_up.show',
                'follow_up.add',
                'student.view',
                'student.add',
                'batch.view',
            ],
            'principal', 'branch_manager' => [
                'dashboard.view',
                ...$this->dashboardSections('institute_dashboard'),
                'branch.view',
                'batch.view',
                'batch.add',
                'batch.edit',
                'teacher.view',
                'staff.view',
                'student.view',
                'student.show',
                'student.add',
                'student.edit',
                'student.status',
                'student.receipt',
                'inquiry.view',
                'inquiry.show',
                'inquiry.receipt',
                'inquiry.add',
                'inquiry.edit',
                'follow_up.view',
                'follow_up.show',
                'follow_up.add',
                'attendance.view',
                'attendance.show',
                'attendance.add',
                'attendance.edit',
                'teacher_attendance.view',
                'teacher_attendance.show',
                'teacher_attendance.add',
                'teacher_attendance.edit',
                'fees.view',
                'fees.show',
                'fees.receipt',
                'exam.view',
                'exam.show',
                'exam.download',
                'homework.view',
                'homework.show',
                'homework.download',
                'homework.add',
                'notice.view',
                'notice.show',
                'notice.add',
                'todo.view',
                'todo.add',
            ],
            default => ['dashboard.view'],
        };

        return Permission::query()
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->whereIn('name', $names)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function propagateTemplatesToInstitutes(): int
    {
        $count = 0;
        $templates = $this->platformTemplates()->keyBy('name');
        $instituteIds = Role::query()
            ->whereNotNull('team_id')
            ->where('team_id', '>', 0)
            ->distinct()
            ->pluck('team_id');

        /** @var array<string, list<int>> $roleIdsBySlug */
        $roleIdsBySlug = [];

        foreach ($instituteIds as $instituteId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $instituteId);

            $local = [];
            foreach (self::TEMPLATE_SLUGS as $slug) {
                $role = Role::query()->firstOrCreate(
                    [
                        'name' => $slug,
                        'guard_name' => 'web',
                        'team_id' => (int) $instituteId,
                    ],
                    [
                        'is_system' => true,
                        'status' => 'active',
                    ]
                );

                $local[$slug] = $role;
                $roleIdsBySlug[$slug][] = (int) $role->id;
            }

            $admin = $local['institute_admin'] ?? null;
            if ($admin) {
                foreach ($local as $slug => $role) {
                    $role->parent_role_id = $slug === 'institute_admin' ? null : $admin->id;
                    $role->save();
                }
                $admin->assignableRoles()->sync(
                    collect($local)->except('institute_admin')->pluck('id')->all()
                );
            }
        }

        foreach (self::TEMPLATE_SLUGS as $slug) {
            /** @var Role|null $template */
            $template = $templates->get($slug);
            $copyIds = $roleIdsBySlug[$slug] ?? [];
            if (! $template || $copyIds === []) {
                continue;
            }

            $permissionIds = $template->permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->bulkReplaceRolePermissions($copyIds, $permissionIds);
            $count += count($copyIds);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return $count;
    }
}
