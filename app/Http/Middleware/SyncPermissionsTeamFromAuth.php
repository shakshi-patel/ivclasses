<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bind Spatie team_id from the authenticated user.
 * Must run AFTER `auth` (not only on the web group) so institute roles/permissions resolve.
 */
class SyncPermissionsTeamFromAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        self::sync($request->user());

        return $next($request);
    }

    public static function sync(?\Illuminate\Contracts\Auth\Authenticatable $user): void
    {
        if (! $user) {
            return;
        }

        $registrar = app(PermissionRegistrar::class);
        $resolvedInstituteId = app()->bound('currentInstituteId')
            ? app('currentInstituteId')
            : null;

        if ($resolvedInstituteId) {
            if (
                method_exists($user, 'isPlatformUser')
                && ! $user->isPlatformUser()
                && ! empty($user->institute_id)
                && (int) $user->institute_id !== (int) $resolvedInstituteId
            ) {
                abort(403, 'You do not belong to this institute.');
            }

            $registrar->setPermissionsTeamId($resolvedInstituteId);

            return;
        }

        if (! empty($user->institute_id)) {
            $registrar->setPermissionsTeamId($user->institute_id);
        } else {
            $registrar->setPermissionsTeamId((int) config('tenancy.platform_team_id', 0));
        }
    }
}
