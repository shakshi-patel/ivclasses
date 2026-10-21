<?php

namespace App\Http\Middleware;

use App\Support\Ajax;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile API accepts only a Sanctum bearer token — a leftover web session cookie
 * must not keep the request authenticated after the token is revoked.
 */
class EnsureMobileBearerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_string($request->bearerToken()) || $request->bearerToken() === '') {
            return Ajax::error('Unauthenticated.', null, 401);
        }

        return $next($request);
    }
}
