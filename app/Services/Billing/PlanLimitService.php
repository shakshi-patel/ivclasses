<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Models\Institute;
use App\Models\Student;
use App\Models\User;
use App\Enums\UserType;

class PlanLimitService
{
    public function branchLimitError(?Institute $institute): ?string
    {
        $max = $this->effectiveMax($institute, 'max_branches');
        if ($max === null || ! $institute) {
            return null;
        }

        $count = Branch::query()->where('institute_id', $institute->id)->count();
        if ($count >= $max) {
            return "Plan limit reached: max {$max} branch(es). Upgrade your plan to add more.";
        }

        return null;
    }

    public function studentLimitError(?Institute $institute): ?string
    {
        $max = $this->effectiveMax($institute, 'max_students');
        if ($max === null || ! $institute) {
            return null;
        }

        $count = Student::query()->where('institute_id', $institute->id)->count();
        if ($count >= $max) {
            return "Plan limit reached: max {$max} student(s). Upgrade your plan to add more.";
        }

        return null;
    }

    public function staffLimitError(?Institute $institute): ?string
    {
        $max = $this->effectiveMax($institute, 'max_staff');
        if ($max === null || ! $institute) {
            return null;
        }

        $count = User::query()
            ->where('institute_id', $institute->id)
            ->whereIn('user_type', [
                UserType::InstituteAdmin,
                UserType::InstituteStaff,
                UserType::BranchManager,
                UserType::Teacher,
            ])
            ->count();

        if ($count >= $max) {
            return "Plan limit reached: max {$max} staff member(s). Upgrade your plan to add more.";
        }

        return null;
    }

    /**
     * Plan limit with optional institute override (null override = use plan).
     */
    public function effectiveMax(?Institute $institute, string $field): ?int
    {
        if (! $institute) {
            return null;
        }

        $overrideField = $field.'_override';
        $override = $institute->{$overrideField} ?? null;
        if ($override !== null) {
            return (int) $override;
        }

        $institute->loadMissing('currentSubscription.plan');
        $planMax = $institute->currentSubscription?->plan?->{$field} ?? null;

        return $planMax === null ? null : (int) $planMax;
    }

    public function limitsFor(?Institute $institute): array
    {
        if (! $institute) {
            return [];
        }

        $institute->loadMissing('currentSubscription.plan');
        $plan = $institute->currentSubscription?->plan;

        return [
            'max_branches' => $this->effectiveMax($institute, 'max_branches'),
            'max_students' => $this->effectiveMax($institute, 'max_students'),
            'max_staff' => $this->effectiveMax($institute, 'max_staff'),
            'plan_max_branches' => $plan?->max_branches,
            'plan_max_students' => $plan?->max_students,
            'plan_max_staff' => $plan?->max_staff,
            'max_branches_override' => $institute->max_branches_override,
            'max_students_override' => $institute->max_students_override,
            'max_staff_override' => $institute->max_staff_override,
            'branches_used' => Branch::query()->where('institute_id', $institute->id)->count(),
            'students_used' => Student::query()->where('institute_id', $institute->id)->count(),
            'staff_used' => User::query()
                ->where('institute_id', $institute->id)
                ->whereIn('user_type', [
                    UserType::InstituteAdmin,
                    UserType::InstituteStaff,
                    UserType::BranchManager,
                    UserType::Teacher,
                ])
                ->count(),
        ];
    }
}
