<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Inquiry;
use App\Models\LoginLog;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentBatch;
use App\Models\Teacher;
use App\Models\Todo;
use App\Services\HomeworkService;
use App\Services\ReportService;
use App\Services\ResultAnnouncementService;
use App\Services\StaffAttendanceService;
use App\Services\StudentAttendanceService;
use App\Services\TodoService;
use App\Support\BranchScope;
use App\Support\CsvExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends AdminController
{
    public function __construct(
        protected ReportService $reports,
        protected StaffAttendanceService $staffAttendance,
        protected StudentAttendanceService $studentAttendance,
        protected HomeworkService $homeworkService,
        protected TodoService $todoService,
        protected ResultAnnouncementService $resultAnnouncementService,
    ) {}

    public function students(Request $request): StreamedResponse
    {
        $this->ensureCan('student.export');
        $user = auth()->user();

        $q = Student::query()
            ->with(['branch:id,name', 'standard:id,name', 'parent:id,father_name,phone'])
            ->orderBy('name');

        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            $q->where('institute_id', $user->institute_id);
        }
        if ($request->filled('branch_id')) {
            $q->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('standard_id')) {
            $q->where('standard_id', (int) $request->input('standard_id'));
        }
        $scope = BranchScope::for($user);
        if ($scope->isRestricted()) {
            $q->whereIn('branch_id', $scope->branchIds() ?: [0]);
        }

        $rows = $q->get()->map(fn (Student $s) => [
            $s->admission_no,
            $s->name,
            $s->branch?->name,
            $s->standard?->name,
            $s->parent?->father_name,
            $s->parent?->phone,
            $s->status,
            $s->admission_date?->format('Y-m-d'),
        ]);

        return CsvExport::download('students.csv', [
            'Admission No', 'Name', 'Branch', 'Standard', 'Father', 'Phone', 'Status', 'Admission Date',
        ], $rows);
    }

    public function teachers(): StreamedResponse
    {
        $this->ensureCan('teacher.view');
        $user = auth()->user();

        $q = Teacher::query()
            ->with(['user:id,name,email,phone,is_active'])
            ->orderBy('id');

        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            $q->where('institute_id', $user->institute_id);
        }

        $rows = $q->get()->map(fn (Teacher $t) => [
            $t->employee_code,
            $t->user?->name,
            $t->user?->email,
            $t->user?->phone,
            $t->specialization,
            $t->status,
            $t->joining_date?->format('Y-m-d'),
        ]);

        return CsvExport::download('teachers.csv', [
            'Employee Code', 'Name', 'Email', 'Phone', 'Specialization', 'Status', 'Joining Date',
        ], $rows);
    }

    public function staff(): StreamedResponse
    {
        $this->ensureCan('staff.view');
        $user = auth()->user();

        $q = Staff::query()
            ->with(['user:id,name,email,phone'])
            ->orderBy('id');

        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            $q->where('institute_id', $user->institute_id);
        }

        $rows = $q->get();
        $roleMap = app(\App\Services\StaffService::class)->roleNamesForStaff($rows);

        $mapped = $rows->map(function (Staff $s) use ($roleMap) {
            $roleKey = $s->user_id && $s->institute_id
                ? "{$s->user_id}:{$s->institute_id}"
                : '';

            return [
                $s->employee_code,
                $s->user?->name,
                $roleMap[$roleKey] ?? '',
                $s->designation,
                $s->user?->email,
                $s->user?->phone,
                $s->status,
            ];
        });

        return CsvExport::download('staff.csv', [
            'Employee Code', 'Name', 'Role', 'Designation', 'Email', 'Phone', 'Status',
        ], $mapped);
    }

    public function inquiries(Request $request): StreamedResponse
    {
        $this->ensureCan('inquiry.export');
        $user = auth()->user();

        $q = Inquiry::query()
            ->with(['branch:id,name', 'standard:id,name'])
            ->orderByDesc('id');

        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            $q->where('institute_id', $user->institute_id);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        $rows = $q->limit(2000)->get()->map(fn (Inquiry $i) => [
            $i->student_name,
            $i->phone,
            $i->branch?->name,
            $i->standard?->name,
            $i->status,
            $i->inquiry_date?->format('Y-m-d'),
            $i->next_follow_up_at?->format('Y-m-d'),
            $i->notes,
        ]);

        return CsvExport::download('inquiries.csv', [
            'Student Name', 'Phone', 'Branch', 'Standard', 'Status', 'Inquiry Date', 'Next Follow Up', 'Notes',
        ], $rows);
    }

    public function followUps(Request $request): StreamedResponse
    {
        $this->ensureCan('follow_up.export');
        $user = auth()->user();

        $q = Inquiry::query()
            ->with(['branch:id,name', 'standard:id,name', 'followUps'])
            ->whereNotIn('status', ['converted', 'lost']);

        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            $q->where('institute_id', $user->institute_id);
        }
        $this->applyInstituteFilter($q, $request);

        $scope = BranchScope::for($user);
        if ($scope->isRestricted()) {
            $ids = $scope->branchIds();
            if ($ids === []) {
                $q->whereRaw('1 = 0');
            } else {
                $q->whereIn('branch_id', $ids);
            }
        }

        if ($request->filled('branch_id')) {
            $q->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('follow_up_date')) {
            $q->whereDate('next_follow_up_at', $request->input('follow_up_date'));
        }
        if ($request->input('status') === 'complete') {
            $q->whereNull('next_follow_up_at');
        } elseif ($request->input('status') === 'pending') {
            $q->whereNotNull('next_follow_up_at');
        }

        $rows = $q->orderByRaw('next_follow_up_at IS NULL')
            ->orderBy('next_follow_up_at')
            ->limit(2000)
            ->get()
            ->map(fn (Inquiry $i) => [
                $i->student_name,
                $i->father_name,
                $i->phone,
                $i->branch?->name,
                $i->standard?->name,
                $i->inquiry_date?->format('Y-m-d'),
                $i->next_follow_up_at?->format('Y-m-d'),
                $i->next_follow_up_at ? 'Pending' : 'Complete',
                $i->followUps->sortByDesc('id')->first()?->remark,
            ]);

        return CsvExport::download('follow-ups.csv', [
            'Student Name', 'Father Name', 'Phone', 'Branch', 'Standard', 'Inquiry Date', 'Next Follow Up', 'Status', 'Latest Remark',
        ], $rows);
    }

    public function todos(Request $request): StreamedResponse
    {
        $this->ensureCan('todo.view');
        $user = auth()->user();

        $filters = [
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'status' => $request->input('status', ''),
            'assignee_id' => $request->input('assignee_id'),
        ];

        $rows = $this->reports->todoReport($filters, $user)->map(fn (Todo $t) => [
            $t->due_date?->format('Y-m-d'),
            $t->tasksDisplay(),
            $t->assignees->pluck('name')->filter()->implode(', ') ?: ($t->assignee?->name ?? ''),
            $t->creator?->name,
            $t->status,
        ]);

        return CsvExport::download('todo-tasks.csv', [
            'Due Date', 'Tasks', 'Assignees', 'Created By', 'Status',
        ], $rows);
    }

    public function roles(): StreamedResponse
    {
        $this->ensureCan('roles.view');
        $rows = $this->reports->rolesReport()->map(fn (array $r) => [
            $r['name'],
            $r['parent'],
            $r['permissions_count'],
            $r['users_count'],
            $r['status'],
            $r['created_at'],
        ]);

        return CsvExport::download('roles.csv', [
            'Role', 'Parent', 'Permissions', 'Users', 'Status', 'Created',
        ], $rows);
    }

    public function batchStudents(Batch $batch): Response
    {
        $this->ensureCan('batch.view');
        $this->assertBatchVisible($batch);

        $batch->load(['institute:id,name', 'branch:id,name', 'standard:id,name']);

        $students = StudentBatch::query()
            ->with(['student:id,name,admission_no,parent_id,status', 'student.parent:id,father_name,phone'])
            ->where('batch_id', $batch->id)
            ->where('status', 'active')
            ->orderBy('roll_no')
            ->orderBy('id')
            ->get();

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $batch->name) ?: 'batch';
        $filename = $safeName.'-students-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.batch_students', compact('batch', 'students'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function teacherAttendance(Request $request, Teacher $teacher): Response
    {
        $this->ensureCanAny(['teacher_attendance.show', 'teacher.tab_attendance']);
        $this->assertTeacherVisible($teacher);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $teacher->load(['user:id,name,email', 'institute:id,name']);
        $branch = $teacher->primaryBranch();
        $branchName = $branch?->name;
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $calendar = $this->staffAttendance->userMonthCalendar(
            (int) $teacher->user_id,
            (int) $teacher->institute_id,
            $branch ? (int) $branch->id : null,
            $year,
            $month
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $teacher->user?->name ?? 'teacher') ?: 'teacher';
        $filename = $safeName.'-attendance-'.sprintf('%04d-%02d', $year, $month).'.pdf';

        return Pdf::loadView('admin.print.pdf.teacher_attendance', compact('teacher', 'calendar', 'year', 'month', 'branchName'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function teacherLoginHistory(Teacher $teacher): Response
    {
        $this->ensureCan('teacher.view');
        $this->assertTeacherVisible($teacher);

        $teacher->load(['user:id,name,email', 'institute:id,name']);

        $logs = LoginLog::query()
            ->where('user_id', $teacher->user_id)
            ->orderByDesc('logged_at')
            ->limit(2000)
            ->get();

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $teacher->user?->name ?? 'teacher') ?: 'teacher';
        $filename = $safeName.'-login-history-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.teacher_login', compact('teacher', 'logs'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function teacherHomework(Teacher $teacher): Response
    {
        $this->ensureCan('homework.view');
        $this->assertTeacherVisible($teacher);

        $teacher->load(['user:id,name,email', 'institute:id,name']);
        $homeworks = $this->homeworkService->listForTeacher($teacher);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $teacher->user?->name ?? 'teacher') ?: 'teacher';
        $filename = $safeName.'-assigned-work-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.teacher_homework', compact('teacher', 'homeworks'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function staffAttendance(Request $request, Staff $staff): Response
    {
        $this->ensureCanAny(['teacher_attendance.show', 'staff.tab_attendance']);
        $this->assertStaffVisible($staff);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $staff->load(['user:id,name,email', 'institute:id,name']);
        $branch = $staff->primaryBranch();
        $branchName = $branch?->name;
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $calendar = $this->staffAttendance->userMonthCalendar(
            (int) $staff->user_id,
            (int) $staff->institute_id,
            $branch ? (int) $branch->id : null,
            $year,
            $month
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $staff->user?->name ?? 'staff') ?: 'staff';
        $filename = $safeName.'-attendance-'.sprintf('%04d-%02d', $year, $month).'.pdf';

        return Pdf::loadView('admin.print.pdf.staff_attendance', compact('staff', 'calendar', 'year', 'month', 'branchName'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function staffLoginHistory(Staff $staff): Response
    {
        $this->ensureCan('staff.view');
        $this->assertStaffVisible($staff);

        $staff->load(['user:id,name,email', 'institute:id,name']);

        $logs = LoginLog::query()
            ->where('user_id', $staff->user_id)
            ->orderByDesc('logged_at')
            ->limit(2000)
            ->get();

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $staff->user?->name ?? 'staff') ?: 'staff';
        $filename = $safeName.'-login-history-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.staff_login', compact('staff', 'logs'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function staffTodos(Staff $staff): Response
    {
        $this->ensureCan('todo.view');
        $this->assertStaffVisible($staff);

        $staff->load(['user:id,name,email', 'institute:id,name']);

        $todos = $staff->user_id
            ? $this->todoService->listForAssignee((int) $staff->user_id, (int) $staff->institute_id)
            : collect();

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $staff->user?->name ?? 'staff') ?: 'staff';
        $filename = $safeName.'-assigned-work-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.staff_todos', compact('staff', 'todos'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function studentAttendance(Request $request, Student $student): Response
    {
        $this->ensureCanAny(['attendance.view', 'student.show']);
        $this->assertStudentVisible($student);

        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $batch = Batch::query()
            ->with(['institute:id,name', 'branch:id,name', 'standard:id,name'])
            ->findOrFail((int) $validated['batch_id']);
        $this->assertBatchVisible($batch);

        $student->load(['institute:id,name', 'branch:id,name', 'standard:id,name']);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $calendar = $this->studentAttendance->studentMonthCalendar(
            $student->id,
            $batch->id,
            $year,
            $month
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $student->name) ?: 'student';
        $filename = $safeName.'-attendance-'.sprintf('%04d-%02d', $year, $month).'.pdf';

        return Pdf::loadView('admin.print.pdf.student_attendance', compact('student', 'batch', 'calendar', 'year', 'month'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function studentResults(Student $student): Response
    {
        $user = auth()->user();
        if (! $user || (! $user->isPlatformUser()
            && ! $user->can('result_announcement.view')
            && ! $user->can('student.view'))) {
            abort(403, 'Unauthorized.');
        }
        $this->assertStudentVisible($student);

        $student->load(['institute:id,name', 'branch:id,name', 'standard:id,name']);
        $results = $this->resultAnnouncementService->listForStudent($student);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $student->name) ?: 'student';
        $filename = $safeName.'-results-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.student_results', compact('student', 'results'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function batchAttendanceReport(Request $request, Batch $batch): Response
    {
        $this->ensureCanAny(['attendance.view', 'batch.tab_attendance_report']);
        $this->assertBatchVisible($batch);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $batch->load(['institute:id,name', 'branch:id,name', 'standard:id,name']);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $report = $this->studentAttendance->batchMonthReport($batch, $year, $month);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $batch->name) ?: 'batch';
        $filename = $safeName.'-attendance-'.sprintf('%04d-%02d', $year, $month).'.pdf';

        return Pdf::loadView('admin.print.pdf.batch_attendance', compact('batch', 'report', 'year', 'month'))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    protected function calendarStatusLabel(?string $cls): string
    {
        return match ($cls) {
            'is-present' => 'Present',
            'is-absent' => 'Absent',
            'is-late' => 'Late',
            'is-holiday' => 'Holiday',
            default => '',
        };
    }

    protected function assertTeacherVisible(Teacher $teacher): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $teacher->institute_id !== (int) $user->institute_id) {
            abort(404);
        }
        BranchScope::for($user)->assertSharesBranchWith($teacher->user);
    }

    protected function assertStaffVisible(Staff $staff): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $staff->institute_id !== (int) $user->institute_id) {
            abort(404);
        }
        BranchScope::for($user)->assertSharesBranchWith($staff->user);
    }

    protected function assertStudentVisible(Student $student): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $student->institute_id !== (int) $user->institute_id) {
            abort(404);
        }
        if ($student->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $student->branch_id);
        }
    }

    protected function assertBatchVisible(Batch $batch): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $batch->institute_id !== (int) $user->institute_id) {
            abort(404);
        }
        if ($batch->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $batch->branch_id);
        }
    }

    protected function ensureCan(string $permission): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can($permission))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
