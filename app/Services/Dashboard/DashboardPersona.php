<?php

namespace App\Services\Dashboard;

use App\Enums\UserType;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Layout preset for institute-side dashboards.
 * The persona only decides order and emphasis; every block is still gated by permissions.
 */
enum DashboardPersona: string
{
    case Admin = 'admin';
    case BranchManager = 'branch_manager';
    case Principal = 'principal';
    case Accountant = 'accountant';
    case Receptionist = 'receptionist';
    case Staff = 'staff';

    /** Staff role slugs (RoleTemplateService::TEMPLATE_SLUGS) in priority order. */
    private const ROLE_PRIORITY = ['principal', 'accountant', 'receptionist'];

    public static function for(User $user): self
    {
        if ($user->user_type === UserType::InstituteAdmin) {
            return self::Admin;
        }

        $roles = self::roleNames($user);

        foreach (self::ROLE_PRIORITY as $slug) {
            if (in_array($slug, $roles, true)) {
                return self::from($slug);
            }
        }

        return $user->user_type === UserType::BranchManager || in_array('branch_manager', $roles, true)
            ? self::BranchManager
            : self::Staff;
    }

    /**
     * @return list<string>
     */
    private static function roleNames(User $user): array
    {
        if (!$user->institute_id) {
            return [];
        }

        $registrar = app(PermissionRegistrar::class);
        $previous = getPermissionsTeamId();
        $registrar->setPermissionsTeamId($user->institute_id);

        try {
            return $user->roles()->pluck('name')->all();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }

    public function label(): ?string
    {
        return match ($this) {
            self::BranchManager => 'Branch Manager',
            self::Principal => 'Principal',
            self::Accountant => 'Accounts',
            self::Receptionist => 'Front Desk',
            default => null,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BranchManager => 'bx-git-branch',
            self::Principal => 'bx-award',
            self::Accountant => 'bx-calculator',
            self::Receptionist => 'bx-id-card',
            default => 'bx-buildings',
        };
    }

    public function subtitle(string $place): string
    {
        return match ($this) {
            self::BranchManager => "Here's how {$place} is doing today.",
            self::Principal => "Academic overview for {$place}: attendance, exams and staff at a glance.",
            self::Accountant => "Collections, dues and expenses for {$place} at a glance.",
            self::Receptionist => "Today's front desk at {$place}: new leads, follow-ups and admissions.",
            default => "Here's what's happening at {$place} today.",
        };
    }

    /**
     * KPI cards in priority order; the first five available are shown.
     *
     * @return list<string>
     */
    public function kpis(): array
    {
        return match ($this) {
            self::BranchManager => ['students', 'attendance', 'staff_attendance', 'fees', 'dues', 'inquiries'],
            self::Principal => ['attendance', 'staff_attendance', 'students', 'dues', 'inquiries', 'fees'],
            self::Accountant => ['fees', 'dues', 'finance', 'students', 'inquiries'],
            self::Receptionist => ['front_desk', 'inquiries', 'students', 'fees', 'dues'],
            default => ['students', 'fees', 'dues', 'attendance', 'inquiries', 'staff_attendance', 'finance'],
        };
    }

    /**
     * Hero quick actions in priority order.
     *
     * @return list<string>
     */
    public function actions(): array
    {
        return match ($this) {
            self::BranchManager => ['attendance_add', 'staff_attendance_add', 'student_add', 'fee_add', 'inquiry_add', 'notice_add'],
            self::Principal => ['attendance_add', 'exam_add', 'notice_add', 'staff_attendance_add', 'student_add'],
            self::Accountant => ['fee_add', 'fee_follow_up', 'income_expense', 'gst_invoice', 'student_add'],
            self::Receptionist => ['inquiry_add', 'student_add', 'follow_up', 'fee_add', 'notice_add'],
            default => ['student_add', 'fee_add', 'attendance_add', 'inquiry_add', 'notice_add'],
        };
    }

    /**
     * Panel rows: block key => column width on xl screens.
     *
     * @return list<array<string, int>>
     */
    public function rows(): array
    {
        return match ($this) {
            self::BranchManager => [
                ['attendance_trend' => 8, 'attention' => 4],
                ['batches_today' => 7, 'staff_today' => 5],
                ['fees_trend' => 7, 'lead_sources' => 5],
                ['batch_attendance' => 6, 'standards' => 6],
            ],
            self::Principal => [
                ['attendance_trend' => 8, 'attention' => 4],
                ['batch_attendance' => 7, 'staff_today' => 5],
                ['batches_today' => 6, 'standards' => 6],
                ['admissions' => 6, 'fees_trend' => 6],
            ],
            self::Accountant => [
                ['fees_trend' => 8, 'attention' => 4],
                ['payment_methods' => 4, 'payments' => 4, 'fee_follow_ups' => 4],
            ],
            self::Receptionist => [
                ['follow_upss' => 7, 'attention' => 5],
                ['lead_sources' => 5, 'admissions' => 7],
            ],
            default => [
                ['fees_trend' => 8, 'attention' => 4],
                ['attendance_trend' => 7, 'lead_sources' => 5],
                ['admissions' => 6, 'standards' => 6],
                ['branches' => 12],
            ],
        };
    }

    /**
     * Compact list panels shown in a three-column grid at the bottom.
     *
     * @return list<string>
     */
    public function lists(): array
    {
        return match ($this) {
            self::BranchManager => ['exams', 'dues', 'inquiries', 'results', 'notices', 'holidays'],
            self::Principal => ['exams', 'results', 'dues', 'inquiries', 'notices', 'holidays'],
            self::Accountant => ['dues', 'notices', 'holidays'],
            self::Receptionist => ['inquiries', 'payments', 'exams', 'notices', 'holidays'],
            default => ['payments', 'dues', 'exams', 'inquiries', 'notices', 'holidays'],
        };
    }
}
