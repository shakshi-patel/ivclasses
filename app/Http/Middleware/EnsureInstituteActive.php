<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstituteActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Spatie teams: bind AFTER auth so institute_admin permissions resolve.
        SyncPermissionsTeamFromAuth::sync($user);

        if (! $user || $user->isPlatformUser()) {
            return $next($request);
        }

        $institute = $user->institute;

        if (! $institute || ! $institute->canAccessCrm()) {
            abort(403, 'Institute is suspended or cancelled.');
        }

        if ($institute->needsBilling() && ! $this->isBillingExemptRoute($request)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trial or subscription expired. Please upgrade your plan.',
                ], 403);
            }

            if (! $request->routeIs('admin.billing.*')) {
                return redirect()
                    ->route('admin.billing.index')
                    ->with('error', 'Your trial or subscription has expired. Please choose a plan to continue.');
            }
        }

        if (
            $institute->isTrialExpired()
            && $request->isMethod('POST')
            && ! $this->isBillingExemptRoute($request)
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trial expired. Please upgrade your plan.',
                ], 403);
            }

            abort(403, 'Trial expired. Please upgrade your plan.');
        }

        return $next($request);
    }

    protected function isBillingExemptRoute(Request $request): bool
    {
        return $request->routeIs(
            'admin.billing.*',
            'profile.*',
            'logout',
        );
    }
}
