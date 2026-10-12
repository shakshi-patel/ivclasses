<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $central = array_map('strtolower', config('tenancy.central_domains', []));
        $platformTeamId = (int) config('tenancy.platform_team_id', 0);

        app()->instance('currentInstitute', null);
        app()->instance('currentInstituteId', null);

        if (in_array($host, $central, true) || $this->isIp($host)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($platformTeamId);

            return $next($request);
        }

        $slug = $this->subdomainSlug($host, $central);

        if (! $slug) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($platformTeamId);

            return $next($request);
        }

        $institute = \App\Models\Institute::query()->where('slug', $slug)->first();

        if (! $institute) {
            abort(404, 'Institute not found.');
        }

        app()->instance('currentInstitute', $institute);
        app()->instance('currentInstituteId', $institute->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($institute->id);

        return $next($request);
    }

    protected function isIp(string $host): bool
    {
        return (bool) filter_var($host, FILTER_VALIDATE_IP);
    }

    protected function subdomainSlug(string $host, array $central): ?string
    {
        foreach ($central as $domain) {
            $suffix = '.'.$domain;
            if (str_ends_with($host, $suffix)) {
                $sub = substr($host, 0, -strlen($suffix));

                return $sub !== '' && $sub !== 'www' ? $sub : null;
            }
        }

        $parts = explode('.', $host);
        if (count($parts) < 3) {
            return null;
        }

        $sub = $parts[0];

        return in_array($sub, ['www', 'app'], true) ? null : $sub;
    }
}
