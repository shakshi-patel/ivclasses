<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Pages for the admin navbar typeahead (Ctrl+/).
 */
final class AdminSearchCatalog
{
    /**
     * @return array{pages: list<array{name: string, icon: string, url: string}>, files: list<array<string, mixed>>, members: list<array<string, mixed>>}
     */
    public static function payload(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $pages = [];

        $add = function (
            string $name,
            string $icon,
            string $routeName,
            string|array|null $ability = null,
            ?callable $when = null
        ) use (&$pages, $user): void {
            if ($when && ! $when($user)) {
                return;
            }

            if ($ability !== null) {
                $ok = false;
                foreach ((array) $ability as $perm) {
                    if ($user?->can($perm)) {
                        $ok = true;
                        break;
                    }
                }
                if (! $ok) {
                    return;
                }
            }

            if (! Route::has($routeName)) {
                return;
            }

            $pages[] = [
                'name' => $name,
                'icon' => $icon,
                'url' => route($routeName),
            ];
        };

        $isPlatform = static fn (?User $u): bool => (bool) $u?->isPlatformUser();
        $isInstitute = static fn (?User $u): bool => (bool) $u && ! $u->isPlatformUser();

        $add('Dashboards', 'bx-home-circle', 'dashboard', 'dashboard.view');
        $add('Subject', 'bx-book', 'admin.master.subject', 'subject.view');
        $add('Standard', 'bx-collection', 'admin.master.standard', 'standard.view');
        $add('Batches', 'bx-group', 'admin.master.batch', 'batch.view');
        $add('Income & Expense Categories', 'bx-category', 'admin.master.income_expense', 'income_expense_category.view');
        $add('Assets Category', 'bx-category-alt', 'admin.master.assets_category', 'assets_category.view');
        $add('Branch', 'bx-git-branch', 'admin.master.branch', 'branch.view');
        $add('Users', 'bx-user', 'admin.users', ['teacher.view', 'staff.view']);
        $add('Inquiry', 'bx-user-pin', 'admin.inquiry', 'inquiry.view');
        $add('Follow Up', 'bx-calendar-check', 'admin.follow_up', 'follow_up.view');
        $add('Students', 'bx-user', 'admin.student', 'student.view');
        $add('Fees', 'bx-rupee', 'admin.fees', 'fees.view');
        $add('Fees Follow Up', 'bx-phone-call', 'admin.fees.follow_up', 'fees_follow_up.view');
        $add('Student Attendance', 'bx-calendar-check', 'admin.attendance.student', 'attendance.view');
        $add('Teacher Attendance', 'bx-calendar', 'admin.attendance.teacher', 'teacher_attendance.view');
        $add('Exam', 'bx-notepad', 'admin.exam', 'exam.view');
        $add('Result Announcement', 'bx-trophy', 'admin.result_announcement', 'result_announcement.view');
        $add('Student Leave Request', 'bx-calendar-x', 'admin.student_leave_request', ['student_leave.view', 'student_leave.add']);
        $add('Staff Leave Request', 'bx-calendar-minus', 'admin.staff_leave_request', ['staff_leave.view', 'staff_leave.add']);
        $add('Income & Expense', 'bx-wallet', 'admin.income_expense', 'income_expense.view');
        $add('Inventory & Assets', 'bx-box', 'admin.inventory_assets', 'inventory.view');
        $add('Study Material', 'bx-book-content', 'admin.study_material', 'study_material.view');
        $add('Homework', 'bx-edit', 'admin.homework', 'homework.view');
        $add('Notice', 'bx-bell', 'admin.notice', 'notice.view');
        $add('Holiday', 'bx-calendar-star', 'admin.holiday', 'holiday.view');
        $add('To Do Task', 'bx-task', 'admin.to_do_task', ['todo.view', 'todo.add']);
        $add('Institutes', 'bx-buildings', 'admin.institutes.index', 'institutes.view');
        $add('Plans', 'bx-purchase-tag-alt', 'admin.plans.index', 'plans.view');
        $add('Module Help', 'bx-help-circle', 'admin.module_help.index', null, $isPlatform);
        $add('Email Templates', 'bx-envelope', 'admin.email_templates.index', null, $isPlatform);
        $add('Project Inquiry', 'bx-message-detail', 'admin.project-inquiries.index', 'project_inquiry.view');
        $add('Demo Calendar', 'bx-calendar-event', 'admin.demo-calendar.index', 'demo_calendar.view');
        $add('Billing', 'bx-credit-card', 'admin.billing.index', 'billing.view');
        $add('Institute Setting', 'bx-buildings', 'admin.classes', 'classes.view', $isInstitute);
        $add('Settings', 'bx-cog', 'admin.settings.index', 'settings.view', $isPlatform);
        $add('Demo Availability', 'bx-time-five', 'admin.demo-availability.index', 'demo_availability.view');
        $add('GST Invoice', 'bx-receipt', 'admin.gst_invoice', 'gst_invoice.view');
        $add('Roles', 'bx-shield-quarter', 'admin.master.roles', 'roles.view');
        $add('Permissions', 'bx-lock-alt', 'admin.master.permissions', 'permissions.view');

        return [
            'pages' => $pages,
            'files' => [],
            'members' => [],
        ];
    }
}
