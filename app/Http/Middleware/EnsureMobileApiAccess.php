<?php

namespace App\Http\Middleware;

use App\Support\Ajax;
use App\Support\MobileApiRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile API is for BM / Principal / Accountant / Receptionist / Teacher / Parent only.
 */
class EnsureMobileApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! MobileApiRole::isAllowedOnMobile($user)) {
            return Ajax::error('This account cannot use the mobile API. Use the web portal.', null, 403);
        }

        if (! $user->is_active) {
            return Ajax::error('This account is inactive.', null, 403);
        }

        return $next($request);
    }
}
