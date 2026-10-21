<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstituteActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->freshAuthenticatedUser($request);

        // Spatie teams: bind AFTER auth so institute_admin permissions resolve.
        SyncPermissionsTeamFromAuth::sync($user);

        if (! $user || $user->isPlatformUser()) {
            return $this->withoutBrowserCache($next($request));
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

        return $this->withoutBrowserCache($next($request));
    }

    protected function freshAuthenticatedUser(Request $request): ?User
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $fresh = User::query()->find($user->id);
        if (! $fresh) {
            return $user;
        }

        auth()->setUser($fresh);
        $request->setUserResolver(static fn () => $fresh);

        return $fresh;
    }

    protected function withoutBrowserCache(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    protected function isBillingExemptRoute(Request $request): bool
    {
        return $request->routeIs(
            'admin.billing.*',
            'profile.*',
            'logout',
            'api.v1.auth.*',
            'api.v1.profile.*',
        );
    }
}
