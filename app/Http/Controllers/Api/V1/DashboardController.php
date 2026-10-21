<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserType;
use App\Services\Dashboard\InstituteDashboardService;
use App\Services\Dashboard\ParentDashboardService;
use App\Services\Dashboard\TeacherDashboardService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    /** @var list<UserType> */
    private const INSTITUTE_TYPES = [
        UserType::InstituteAdmin,
        UserType::InstituteStaff,
        UserType::BranchManager,
    ];

    public function show(Request $request): JsonResponse
    {
        $this->ensureCan('dashboard.view');

        $user = $this->actor();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        if (in_array($user->user_type, self::INSTITUTE_TYPES, true)) {
            $dashboard = $this->instituteDashboard($request);
            $summary = $dashboard->summary();
            $plan = $dashboard->planStatus();

            return Ajax::success('OK', [
                'kind' => 'institute',
                'greeting' => $greeting,
                'name' => $user->name,
                'persona' => $dashboard->persona()->value,
                'place_name' => $dashboard->placeName(),
                'selected_branch_id' => $dashboard->selectedBranchId(),
                'branches' => $dashboard->branchOptions()->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'code' => $b->code,
                    'status' => $b->status,
                ])->values()->all(),
                'summary' => $summary,
                'kpis' => $dashboard->kpis($summary),
                'quick_actions' => $dashboard->quickActions(),
                'attention' => $dashboard->attention(),
                'plan' => $plan ? [
                    'name' => $plan['name'],
                    'is_trial' => $plan['is_trial'],
                    'ends_at' => $plan['ends_at']?->toIso8601String(),
                    'days_left' => $plan['days_left'],
                ] : null,
                'allowed_widgets' => $dashboard->allowedWidgets(),
            ]);
        }

        if ($user->isTeacher()) {
            $dashboard = TeacherDashboardService::for($user);

            return Ajax::success('OK', [
                'kind' => 'teacher',
                'greeting' => $greeting,
                'name' => $user->name,
                'summary' => $dashboard->summary(),
                'attention' => $dashboard->attention(),
                'quick_actions' => collect($dashboard->quickActions())->map(fn (array $action) => [
                    'key' => match ($action['route']) {
                        'admin.attendance.student' => 'mark_attendance',
                        'admin.homework.add' => 'give_homework',
                        'admin.exam.add' => 'schedule_exam',
                        'admin.study_material.add' => 'upload_material',
                        'admin.staff_leave_request' => 'apply_leave',
                        default => $action['route'],
                    },
                    'icon' => $action['icon'],
                    'label' => $action['label'],
                ])->values()->all(),
                'batches' => $dashboard->batches()->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'branch_id' => $b->branch_id,
                    'branch_name' => $b->branch?->name,
                    'standard_id' => $b->standard_id,
                    'standard_name' => $b->standard?->name,
                    'status' => $b->status,
                    'start_time' => $b->start_time,
                    'end_time' => $b->end_time,
                    'is_primary' => (bool) $b->getAttribute('is_primary'),
                    'is_marked' => (bool) $b->getAttribute('is_marked'),
                    'is_live' => (bool) $b->getAttribute('is_live'),
                    'present_count' => (int) $b->getAttribute('present_count'),
                    'absent_count' => (int) $b->getAttribute('absent_count'),
                    'students_count' => (int) $b->getAttribute('students_count'),
                    'subjects' => $b->subjects->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                    ])->values()->all(),
                ])->values()->all(),
                'upcoming_exams' => $dashboard->upcomingExams()->map(fn ($exam) => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'exam_date' => $exam->exam_date?->toDateString(),
                    'start_time' => $exam->start_time,
                    'total_marks' => $exam->total_marks,
                    'batch' => $exam->batch ? ['id' => $exam->batch->id, 'name' => $exam->batch->name] : null,
                    'subject' => $exam->subject ? ['id' => $exam->subject->id, 'name' => $exam->subject->name] : null,
                ])->values()->all(),
                'recent_homework' => $dashboard->recentHomework()->map(fn ($hw) => [
                    'id' => $hw->id,
                    'title' => $hw->title,
                    'homework_date' => $hw->homework_date?->toDateString(),
                    'due_date' => $hw->due_date?->toDateString(),
                    'batch' => $hw->batch ? ['id' => $hw->batch->id, 'name' => $hw->batch->name] : null,
                    'subject' => $hw->subject ? ['id' => $hw->subject->id, 'name' => $hw->subject->name] : null,
                ])->values()->all(),
                'notices' => $dashboard->notices()->map(fn ($notice) => [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'notice_date' => $notice->notice_date?->toDateString(),
                    'audience' => $notice->audience,
                ])->values()->all(),
                'allowed_widgets' => $dashboard->allowedWidgets(),
            ]);
        }

        if ($user->isParent()) {
            $dashboard = ParentDashboardService::for(
                $user,
                $request->filled('student_id') ? (int) $request->input('student_id') : null,
            );
            $child = $dashboard->child();
            $stats = $dashboard->childStats();

            return Ajax::success('OK', [
                'kind' => 'parent',
                'greeting' => $greeting,
                'name' => $user->name,
                'selected_student_id' => $child?->id,
                'children' => $dashboard->children()->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'admission_no' => $s->admission_no,
                    'photo_url' => $s->photo_path ? asset('storage/'.$s->photo_path) : null,
                    'standard' => $s->standard ? ['id' => $s->standard->id, 'name' => $s->standard->name] : null,
                    'branch' => $s->branch ? ['id' => $s->branch->id, 'name' => $s->branch->name] : null,
                    'attendance_rate' => $stats[$s->id]['attendance'] ?? null,
                    'fees_due' => $stats[$s->id]['due'] ?? 0,
                ])->values()->all(),
                'summary' => $dashboard->summary(),
                'attention' => $dashboard->attention(),
                'quick_actions' => collect($dashboard->quickActions())->map(fn (array $action) => [
                    'key' => match ($action['route']) {
                        'admin.student_leave_request' => 'apply_leave',
                        'admin.homework' => 'homework',
                        'admin.exam' => 'exams',
                        'admin.result_announcement' => 'results',
                        'admin.study_material' => 'study_material',
                        default => $action['route'],
                    },
                    'icon' => $action['icon'],
                    'label' => $action['label'],
                    'student_id' => $action['params']['student_id'] ?? $child?->id,
                ])->values()->all(),
                'enrollments' => $dashboard->enrollments()->map(fn ($row) => [
                    'id' => $row->id,
                    'roll_no' => $row->roll_no,
                    'batch' => $row->batch ? [
                        'id' => $row->batch->id,
                        'name' => $row->batch->name,
                        'start_time' => $row->batch->start_time,
                        'end_time' => $row->batch->end_time,
                    ] : null,
                    'subject' => $row->subject ? ['id' => $row->subject->id, 'name' => $row->subject->name] : null,
                    'teachers' => $row->batch?->teachers?->map(fn ($t) => [
                        'id' => $t->id,
                        'name' => $t->name,
                    ])->values()->all() ?? [],
                ])->values()->all(),
                'upcoming_exams' => $dashboard->upcomingExams()->map(fn ($exam) => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'exam_date' => $exam->exam_date?->toDateString(),
                    'start_time' => $exam->start_time,
                    'total_marks' => $exam->total_marks,
                    'batch' => $exam->batch ? ['id' => $exam->batch->id, 'name' => $exam->batch->name] : null,
                    'subject' => $exam->subject ? ['id' => $exam->subject->id, 'name' => $exam->subject->name] : null,
                ])->values()->all(),
                'homework' => $dashboard->homework()->map(fn ($hw) => [
                    'id' => $hw->id,
                    'title' => $hw->title,
                    'homework_date' => $hw->homework_date?->toDateString(),
                    'due_date' => $hw->due_date?->toDateString(),
                    'subject' => $hw->subject ? ['id' => $hw->subject->id, 'name' => $hw->subject->name] : null,
                    'teacher' => $hw->teacher ? ['id' => $hw->teacher->id, 'name' => $hw->teacher->name] : null,
                ])->values()->all(),
                'results' => $dashboard->publishedResults()->map(fn ($result) => [
                    'id' => $result->id,
                    'exam_id' => $result->exam_id,
                    'title' => $result->exam?->title,
                    'exam_date' => $result->exam?->exam_date?->toDateString(),
                    'subject' => $result->exam?->subject ? [
                        'id' => $result->exam->subject->id,
                        'name' => $result->exam->subject->name,
                    ] : null,
                    'marks_obtained' => $result->marks_obtained,
                    'total_marks' => $result->exam?->total_marks,
                    'percent' => $result->percent,
                    'grade' => $result->grade,
                ])->values()->all(),
                'due_invoices' => $dashboard->dueInvoices()->map(fn ($inv) => [
                    'id' => $inv->id,
                    'title' => $inv->title,
                    'net_amount' => (float) $inv->net_amount,
                    'paid_amount' => (float) $inv->paid_amount,
                    'due_amount' => (float) $inv->due_amount,
                    'due_date' => $inv->due_date?->toDateString(),
                ])->values()->all(),
                'notices' => $dashboard->notices()->map(fn ($notice) => [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'notice_date' => $notice->notice_date?->toDateString(),
                    'audience' => $notice->audience,
                ])->values()->all(),
                'holidays' => $dashboard->holidays()->map(fn ($holiday) => [
                    'id' => $holiday->id,
                    'title' => $holiday->title,
                    'start_date' => $holiday->start_date?->toDateString(),
                    'end_date' => $holiday->end_date?->toDateString(),
                ])->values()->all(),
                'leaves' => $dashboard->leaves()->map(fn ($leave) => [
                    'id' => $leave->id,
                    'from_date' => $leave->from_date?->toDateString(),
                    'to_date' => $leave->to_date?->toDateString(),
                    'status' => $leave->status,
                    'reason' => $leave->reason,
                ])->values()->all(),
                'allowed_widgets' => $dashboard->allowedWidgets(),
            ]);
        }

        return Ajax::error('No dashboard for this account.', null, 403);
    }

    public function widget(Request $request, string $widget): JsonResponse
    {
        $this->ensureCan('dashboard.view');

        $user = $this->actor();
        $dashboard = match (true) {
            in_array($user->user_type, self::INSTITUTE_TYPES, true) => $this->instituteDashboard($request),
            $user->isTeacher() => TeacherDashboardService::for($user),
            $user->isParent() => ParentDashboardService::for(
                $user,
                $request->filled('student_id') ? (int) $request->input('student_id') : null,
            ),
            default => null,
        };

        if (! $dashboard || ! $dashboard->allowsWidget($widget)) {
            return Ajax::error('Unauthorized.', null, 403);
        }

        return Ajax::success('OK', $dashboard->chart($widget));
    }

    private function instituteDashboard(Request $request): InstituteDashboardService
    {
        return InstituteDashboardService::for(
            $this->actor(),
            $request->filled('branch_id') ? (int) $request->input('branch_id') : null,
        );
    }
}
