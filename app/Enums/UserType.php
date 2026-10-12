<?php

namespace App\Enums;

enum UserType: string
{
    case PlatformAdmin = 'platform_admin';
    case InstituteAdmin = 'institute_admin';
    case InstituteStaff = 'institute_staff';
    case BranchManager = 'branch_manager';
    case Teacher = 'teacher';
    case Parent = 'parent';
    case Student = 'student';
}
