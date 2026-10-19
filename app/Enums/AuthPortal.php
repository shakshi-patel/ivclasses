<?php

namespace App\Enums;

enum AuthPortal: string
{
    case Platform = 'platform';
    case Institute = 'institute';
    case Principal = 'principal';
    case Branch = 'branch';
    case Parent = 'parent';

    public function label(): string
    {
        return match ($this) {
            self::Platform => 'Platform Admin',
            self::Institute => 'Institute CRM',
            self::Principal => 'Principal',
            self::Branch => 'Branch Manager',
            self::Parent => 'Parent Portal',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Platform => 'Class Flow super admin & developer access.',
            self::Institute => 'Institute admin, accountant, receptionist and teachers.',
            self::Principal => 'Principal workspace — assigned branch academics & operations.',
            self::Branch => 'Branch manager workspace — one branch operations.',
            self::Parent => 'View homework, notices, attendance and results.',
        };
    }

    public function routeName(): string
    {
        return 'login.'.$this->value;
    }

    public function storeRouteName(): string
    {
        return 'login.'.$this->value.'.store';
    }

    /**
     * @return list<UserType>
     */
    public function allowedUserTypes(): array
    {
        return match ($this) {
            self::Platform => [UserType::PlatformAdmin],
            self::Institute => [
                UserType::InstituteAdmin,
                UserType::InstituteStaff,
                UserType::Teacher,
            ],
            self::Principal => [
                UserType::InstituteStaff,
                UserType::InstituteAdmin,
            ],
            self::Branch => [
                UserType::BranchManager,
            ],
            self::Parent => [UserType::Parent],
        };
    }

    public static function tryFromRoute(?string $portal): ?self
    {
        return $portal ? self::tryFrom($portal) : null;
    }
}
