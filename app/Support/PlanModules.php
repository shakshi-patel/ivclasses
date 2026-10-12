<?php

namespace App\Support;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolve CRM modules from the institute's current plan (modules_json),
 * then apply per-institute extras / blocks.
 *
 * effective = CORE + plan modules + modules_extra − modules_blocked
 * (CORE modules cannot be blocked.)
 */
class PlanModules
{
    /**
     * Always available so institutes can operate basics + upgrade.
     *
     * @var list<string>
     */
    public const CORE = [
        'dashboard',
        'classes',
        'branch',
        'batch',
        'standard',
        'subject',
        'teacher',
        'staff',
        'student',
        'roles',
        'permissions',
        'billing',
    ];

    /**
     * @return array<string, string> module key => label
     */
    public static function crmCatalog(): array
    {
        $out = [];

        foreach (config('permission_matrix.modules', []) as $key => $meta) {
            if (! ($meta['enabled'] ?? false)) {
                continue;
            }
            if (in_array($key, Tenancy::PLATFORM_MODULES, true) || isset($meta['plan_module'])) {
                continue;
            }
            $out[$key] = (string) ($meta['name'] ?? $key);
        }

        return $out;
    }

    /**
     * Modules from plan only (plus CORE) — ignores institute overrides.
     *
     * @return list<string>
     */
    public static function fromPlan(?Institute $institute): array
    {
        if (! $institute) {
            return self::CORE;
        }

        $institute->loadMissing('currentSubscription.plan');
        $fromPlan = self::expand($institute->currentSubscription?->plan?->modules_json);

        return array_values(array_unique(array_merge(self::CORE, $fromPlan)));
    }

    /**
     * @return list<string>
     */
    public static function for(?Institute $institute): array
    {
        $base = self::fromPlan($institute);

        if (! $institute) {
            return $base;
        }

        $extra = self::expand($institute->modules_extra_json);
        $blocked = array_values(array_diff(
            self::expand($institute->modules_blocked_json),
            self::CORE
        ));

        return array_values(array_unique(array_diff(
            array_merge($base, $extra),
            $blocked
        )));
    }

    public static function allows(?Institute $institute, string $module): bool
    {
        if ($module === '' || in_array($module, Tenancy::PLATFORM_MODULES, true)) {
            return true;
        }

        return in_array($module, self::for($institute), true);
    }

    public static function allowsAbility(?User $user, string $ability): bool
    {
        if (! $user || $user->isPlatformUser()) {
            return true;
        }

        $module = self::moduleFromAbility($ability);
        if ($module === null) {
            return true;
        }

        return self::allows($user->institute, $module);
    }

    public static function moduleFromAbility(string $ability): ?string
    {
        if (! str_contains($ability, '.')) {
            return null;
        }

        $module = explode('.', $ability, 2)[0];

        return $module !== '' ? self::planModuleOf($module) : null;
    }

    /**
     * Plan module a permission module is entitled under (matrix `plan_module`, else itself).
     */
    public static function planModuleOf(string $module): string
    {
        return (string) (config("permission_matrix.modules.{$module}.plan_module") ?? $module);
    }

    /**
     * Feature modules selectable on a plan (everything CRM except CORE).
     *
     * @return array<string, string>
     */
    public static function sellableCatalog(): array
    {
        $out = [];

        foreach (self::crmCatalog() as $key => $label) {
            if (! in_array($key, self::CORE, true)) {
                $out[$key] = $label;
            }
        }

        asort($out);

        return $out;
    }

    /**
     * @param  list<string>|null  $modules
     * @return list<string>
     */
    public static function expand(?array $modules): array
    {
        if ($modules === null || $modules === []) {
            return [];
        }

        $catalog = array_keys(self::crmCatalog());

        if (in_array('*', $modules, true)) {
            return $catalog;
        }

        return array_values(array_intersect($modules, $catalog));
    }

    /**
     * Short label for plan modules_json (UI lists / badges).
     *
     * @param  list<string>|null  $modules
     */
    public static function summaryLabel(?array $modules): string
    {
        if ($modules === null || $modules === []) {
            return 'Core only';
        }

        if (in_array('*', $modules, true)) {
            return 'All modules';
        }

        $count = count(self::expand($modules));

        return $count === 1 ? '1 feature' : "{$count} features";
    }

    /**
     * Human labels for module keys (sellable + core catalog).
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function labelsFor(array $keys): array
    {
        $catalog = self::crmCatalog();

        return array_values(array_map(
            fn (string $key) => $catalog[$key] ?? $key,
            $keys
        ));
    }

    public static function filterPermissions(Collection $permissions, ?Institute $institute): Collection
    {
        $enabled = self::for($institute);

        return $permissions->filter(function ($permission) use ($enabled) {
            $module = $permission->module
                ? self::planModuleOf((string) $permission->module)
                : self::moduleFromAbility((string) $permission->name);

            if (! $module || in_array($module, Tenancy::PLATFORM_MODULES, true)) {
                return false;
            }

            return in_array($module, $enabled, true);
        })->values();
    }
}
