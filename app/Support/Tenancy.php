<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Tenancy
{
    /** @var list<string> */
    public const PLATFORM_MODULES = [
        'institutes',
        'plans',
        'project_inquiry',
        'demo_availability',
        'demo_calendar',
        'demo_booking',
        'settings',
    ];

    public static function teamId(): int|string|null
    {
        return getPermissionsTeamId();
    }

    public static function isPlatformTeam(): bool
    {
        $teamId = self::teamId();

        return $teamId === null || (int) $teamId === 0;
    }

    public static function rolesQuery(): Builder
    {
        $teamId = self::teamId();

        return Role::query()->where(function (Builder $q) use ($teamId) {
            if ($teamId === null || (int) $teamId === 0) {
                $q->whereNull('team_id')->orWhere('team_id', 0);
            } else {
                $q->where('team_id', $teamId);
            }
        });
    }

    /**
     * Active permissions for institute roles (excludes platform modules).
     *
     * @return Collection<int, Permission>
     */
    public static function institutePermissions(): Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->whereNotIn('module', self::PLATFORM_MODULES)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Active permissions visible for assign UI (hide platform modules for institute teams).
     *
     * @return Collection<int, Permission>
     */
    public static function assignablePermissions(?User $user = null): Collection
    {
        $user ??= auth()->user();
        $hidePlatform = ($user && ! $user->isPlatformUser()) || ! self::isPlatformTeam();

        if ($hidePlatform) {
            $permissions = self::institutePermissions();

            if ($user && ! $user->isPlatformUser()) {
                return PlanModules::filterPermissions($permissions, $user->institute);
            }

            return $permissions;
        }

        return Permission::query()
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    public static function permissionsGroupedByModule(?User $user = null): Collection
    {
        return self::assignablePermissions($user)->groupBy(fn (Permission $p) => $p->module ?: 'other');
    }
}
