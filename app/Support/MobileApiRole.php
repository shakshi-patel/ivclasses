<?php

namespace App\Support;

use App\Enums\UserType;
use App\Models\User;

/**
 * Resolve the mobile "login role" for Sanctum token abilities.
 * Platform Admin and Institute Admin are not mobile roles (PRD §1).
 */
final class MobileApiRole
{
    public const BRANCH_MANAGER = 'branch_manager';

    public const PRINCIPAL = 'principal';

    public const ACCOUNTANT = 'accountant';

    public const RECEPTIONIST = 'receptionist';

    public const TEACHER = 'teacher';

    public const PARENT = 'parent';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::BRANCH_MANAGER,
            self::PRINCIPAL,
            self::ACCOUNTANT,
            self::RECEPTIONIST,
            self::TEACHER,
            self::PARENT,
        ];
    }

    public static function isAllowedOnMobile(User $user): bool
    {
        if ($user->isPlatformUser() || $user->user_type === UserType::InstituteAdmin) {
            return false;
        }

        return self::resolve($user) !== null;
    }

    public static function resolve(User $user): ?string
    {
        if ($user->isPlatformUser() || $user->user_type === UserType::InstituteAdmin) {
            return null;
        }

        if ($user->user_type === UserType::Parent || $user->isParent()) {
            return self::PARENT;
        }

        if ($user->user_type === UserType::Teacher || $user->isTeacher()) {
            return self::TEACHER;
        }

        // Principal before BM so a dual-hat user matches DashboardPersona.
        if ($user->isPrincipal()) {
            return self::PRINCIPAL;
        }

        if ($user->isBranchManager()) {
            return self::BRANCH_MANAGER;
        }

        if ($user->hasRole('accountant')) {
            return self::ACCOUNTANT;
        }

        if ($user->hasRole('receptionist')) {
            return self::RECEPTIONIST;
        }

        if ($user->hasRole('principal')) {
            return self::PRINCIPAL;
        }

        if ($user->hasRole('teacher')) {
            return self::TEACHER;
        }

        // Institute staff with a custom role still get a token; ability = staff.
        if ($user->user_type === UserType::InstituteStaff) {
            $role = $user->getRoleNames()->first();

            return $role ? (string) $role : 'staff';
        }

        return null;
    }

    public static function ability(string $role): string
    {
        return 'mobile:'.$role;
    }
}
