<?php

namespace App\Support;

use App\Enums\AuthPortal;
use App\Enums\UserType;
use App\Models\Institute;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthPortalGuard
{
    public static function currentTenant(): ?Institute
    {
        $institute = app()->bound('currentInstitute') ? app('currentInstitute') : null;

        return $institute instanceof Institute ? $institute : null;
    }

    /**
     * @throws ValidationException
     */
    public static function assertUserMatchesPortal(User $user, AuthPortal $portal, ?Institute $tenant = null): void
    {
        $tenant ??= self::currentTenant();

        if (! in_array($user->user_type, $portal->allowedUserTypes(), true)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($portal === AuthPortal::Platform) {
            if (! $user->isPlatformUser()) {
                throw ValidationException::withMessages([
                    'email' => trans('auth.failed'),
                ]);
            }

            return;
        }

        if ($user->isPlatformUser()) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($tenant && (int) $user->institute_id !== (int) $tenant->id) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $isPrincipal = $user->isPrincipal();
        $isBranchManager = $user->isBranchManager();

        match ($portal) {
            AuthPortal::Principal => self::assert(
                $isPrincipal && ! $isBranchManager,
            ),
            AuthPortal::Branch => self::assert(
                $isBranchManager && ! $isPrincipal,
            ),
            // Institute admin is often manager_user_id of the main branch (no separate
            // branch logins). That must not block the institute portal — only a real
            // Branch Manager account belongs on the branch login.
            AuthPortal::Institute => self::assert(
                ! $isPrincipal && $user->user_type !== UserType::BranchManager,
            ),
            default => null,
        };
    }

    protected static function assert(bool $ok): void
    {
        if ($ok) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    public static function logoutRedirectPortal(?User $user): AuthPortal
    {
        if (! $user) {
            return AuthPortal::Institute;
        }

        if ($user->user_type === UserType::PlatformAdmin) {
            return AuthPortal::Platform;
        }

        if ($user->user_type === UserType::Parent) {
            return AuthPortal::Parent;
        }

        if ($user->isPrincipal()) {
            return AuthPortal::Principal;
        }

        if ($user->isBranchManager()) {
            return AuthPortal::Branch;
        }

        return AuthPortal::Institute;
    }
}
