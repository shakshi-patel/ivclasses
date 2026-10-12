<?php

namespace App\Support;

/**
 * Maps admin route names → CRM module_key for Module Help wizard.
 * Index routes (exact map key) get How it works + first-visit auto-open.
 *
 * CTA suggestions are page URLs only (no modal deep-links):
 * - List: index path
 * - Add: named *.add / *.create route when it exists
 * - Edit: index?open=edit → follows first Edit page link (AdminAjax.followFirstEditLink)
 */
class ModuleHelpRegistry
{
    public const OPEN_EDIT_QUERY = 'open=edit';

    /**
     * @return array<string, string>
     */
    public static function routeMap(): array
    {
        return [
            'dashboard' => 'dashboard',
            'admin.master.branch' => 'branch',
            'admin.master.batch' => 'batch',
            'admin.master.teacher' => 'teacher',
            'admin.master.subject' => 'subject',
            'admin.master.standard' => 'standard',
            'admin.master.income_expense' => 'income_expense',
            'admin.master.assets_category' => 'assets_category',
            'admin.master.roles' => 'roles',
            'admin.master.permissions' => 'permissions',
            'admin.classes' => 'classes',
            'admin.student' => 'student',
            'admin.inquiry' => 'inquiry',
            'admin.fees' => 'fees',
            'admin.fees.follow_up' => 'fees',
            'admin.staff' => 'staff',
            'admin.follow_up' => 'follow_up',
            'admin.to_do_task' => 'to_do_task',
            'admin.attendance.student' => 'attendance',
            'admin.attendance.teacher' => 'teacher_attendance',
            'admin.exam' => 'exam',
            'admin.result_announcement' => 'result_announcement',
            'admin.timetable' => 'timetable',
            'admin.study_material' => 'study_material',
            'admin.homework' => 'homework',
            'admin.notice' => 'notice',
            'admin.holiday' => 'holiday',
            'admin.student_leave_request' => 'student_leave',
            'admin.staff_leave_request' => 'staff_leave',
            'admin.leave' => 'leave',
            'admin.expense' => 'expense',
            'admin.income' => 'income',
            'admin.assets' => 'assets',
            'admin.reports' => 'reports',
            'admin.billing.index' => 'billing',
        ];
    }

    public static function resolve(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $map = self::routeMap();

        if (isset($map[$routeName])) {
            return $map[$routeName];
        }

        $bestPrefix = null;
        foreach (array_keys($map) as $prefix) {
            if (str_starts_with($routeName, $prefix.'.')) {
                if ($bestPrefix === null || strlen($prefix) > strlen($bestPrefix)) {
                    $bestPrefix = $prefix;
                }
            }
        }

        return $bestPrefix !== null ? $map[$bestPrefix] : null;
    }

    public static function isIndexRoute(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        return array_key_exists($routeName, self::routeMap());
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public static function suggestedCtasForModule(?string $moduleKey): array
    {
        if (! $moduleKey) {
            return [];
        }

        $indexRoute = null;
        foreach (self::routeMap() as $routeName => $key) {
            if ($key === $moduleKey) {
                $indexRoute = $routeName;
                break;
            }
        }

        if (! $indexRoute || ! \Illuminate\Support\Facades\Route::has($indexRoute)) {
            return [];
        }

        $label = \App\Support\PlanModules::crmCatalog()[$moduleKey]
            ?? str_replace('_', ' ', ucfirst($moduleKey));

        $indexPath = self::appPathFromRoute($indexRoute);
        if ($indexPath === null) {
            return [];
        }

        $out = [
            [
                'label' => 'Open '.$label.' list',
                'url' => $indexPath,
            ],
        ];

        foreach ([$indexRoute.'.add', $indexRoute.'.create'] as $addRoute) {
            if (! \Illuminate\Support\Facades\Route::has($addRoute)) {
                continue;
            }
            $addPath = self::appPathFromRoute($addRoute);
            if ($addPath === null) {
                continue;
            }
            $out[] = [
                'label' => 'Add '.$label,
                'url' => $addPath,
            ];
            break;
        }

        // Edit page only (no modal): list?open=edit → first /edit link.
        if (\Illuminate\Support\Facades\Route::has($indexRoute.'.edit')) {
            $sep = str_contains($indexPath, '?') ? '&' : '?';
            $out[] = [
                'label' => 'Edit '.$label,
                'url' => $indexPath.$sep.self::OPEN_EDIT_QUERY,
            ];
        }

        return $out;
    }

    protected static function appPathFromRoute(string $routeName): ?string
    {
        try {
            $path = parse_url(route($routeName), PHP_URL_PATH);
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($path) || $path === '') {
            return null;
        }

        $base = rtrim((string) request()->getBasePath(), '/');
        if ($base !== '' && ! str_starts_with($path, $base.'/') && $path !== $base) {
            if (str_starts_with($path, '/admin')) {
                return $base.$path;
            }
        }

        return $path;
    }
}
