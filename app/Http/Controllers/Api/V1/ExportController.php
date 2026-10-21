<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Batch;
use App\Models\Inquiry;
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
use App\Support\BranchScope;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends ApiController
{
    public function __construct(
        protected ReportService $reports,
        protected StaffAttendanceService $staffAttendance,
        protected StudentAttendanceService $studentAttendance,
        protected HomeworkService $homeworkService,
        protected ResultAnnouncementService $resultAnnouncementService,
    ) {}

    public function students(Request $request): StreamedResponse
    {
        $this->ensureCan('student.export');

        $q = Student::query()
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['branch:id,name', 'standard:id,name', 'parent:id,father_name,phone'])
            ->orderBy('name');

        if ($request->filled('branch_id')) {
            $q->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('standard_id')) {
            $q->where('standard_id', (int) $request->input('standard_id'));
        }

        $user = $this->actor();
        if ($user->isTeacher()) {
            $ids = \App\Support\OwnBatchScope::studentIds($user);
            $q->whereIn('id', $ids->isEmpty() ? [0] : $ids->all());
        } else {
            $scope = BranchScope::for($user);
            if ($scope->isRestricted()) {
                $q->whereIn('branch_id', $scope->branchIds() ?: [0]);
            }
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

    public function studentAttendance(Request $request, Student $student): StreamedResponse
    {
        if ($this->actor()->isParent()) {
            $this->ensureCanAny([
                'attendance.view',
                'student.show',
                'parent_dashboard.kpi_attendance',
                'parent_dashboard.chart_attendance',
            ]);
        } else {
            $this->ensureCanAny(['attendance.view', 'student.show']);
        }
        $this->assertStudentVisible($student);

        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $batch = Batch::query()->findOrFail((int) $validated['batch_id']);
        $this->assertBatchVisible($batch);

        $calendar = $this->studentAttendance->studentMonthCalendar(
            $student->id,
            $batch->id,
            (int) $validated['year'],
            (int) $validated['month']
        );

        $rows = [];
        foreach ($calendar['days'] as $day => $cls) {
            $rows[] = [
                sprintf('%04d-%02d-%02d', $calendar['year'], $calendar['month'], $day),
                $this->calendarStatusLabel($cls),
            ];
        }

        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $student->name) ?: 'student';

        return CsvExport::download($safe.'-attendance.csv', ['Date', 'Status'], $rows);
    }

    public function studentResults(Student $student): StreamedResponse
    {
        $this->ensureCanAny(['result_announcement.view', 'student.show', 'student.view']);
        $this->assertStudentVisible($student);

        $rows = $this->resultAnnouncementService->listForStudent($student)->map(function ($announcement) {
            $result = $announcement->studentResult;

            return [
                $announcement->exam?->title ?? $announcement->title,
                $announcement->exam?->exam_date?->format('Y-m-d') ?? $announcement->result_date?->format('Y-m-d'),
                $announcement->exam?->subject?->name,
                $announcement->exam?->total_marks,
                $result?->marks_obtained,
                $result?->remark,
                $result?->attendance_status,
            ];
        });

        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $student->name) ?: 'student';

        return CsvExport::download($safe.'-results.csv', [
            'Exam', 'Date', 'Subject', 'Total Marks', 'Marks Obtained', 'Remark', 'Attendance',
        ], $rows);
    }

    public function inquiries(Request $request): StreamedResponse
    {
        $this->ensureCan('inquiry.export');

        $q = Inquiry::query()
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['branch:id,name', 'standard:id,name'])
            ->orderByDesc('id');

        $scope = BranchScope::for($this->actor());
        if ($scope->isRestricted()) {
            $q->whereIn('branch_id', $scope->branchIds() ?: [0]);
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

        $q = Inquiry::query()
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['branch:id,name', 'standard:id,name', 'followUps'])
            ->whereNotIn('status', ['converted', 'lost']);

        $scope = BranchScope::for($this->actor());
        if ($scope->isRestricted()) {
            $q->whereIn('branch_id', $scope->branchIds() ?: [0]);
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

        $rows = $this->reports->todoReport([
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'status' => $request->input('status', ''),
            'assignee_id' => $request->input('assignee_id'),
        ], $this->actor())->map(fn (Todo $t) => [
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

    public function batchStudents(Batch $batch): StreamedResponse
    {
        $this->ensureCanAny(['batch.show', 'batch.view']);
        $this->assertBatchVisible($batch);

        $rows = StudentBatch::query()
            ->with(['student:id,name,admission_no,parent_id,status', 'student.parent:id,father_name,phone'])
            ->where('batch_id', $batch->id)
            ->where('status', 'active')
            ->orderBy('roll_no')
            ->orderBy('id')
            ->get()
            ->map(fn (StudentBatch $row) => [
                $row->roll_no,
                $row->student?->admission_no,
                $row->student?->name,
                $row->student?->parent?->father_name,
                $row->student?->parent?->phone,
                $row->student?->status,
            ]);

        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $batch->name) ?: 'batch';

        return CsvExport::download($safe.'-students.csv', [
            'Roll No', 'Admission No', 'Name', 'Father', 'Phone', 'Status',
        ], $rows);
    }

    public function batchAttendanceReport(Request $request, Batch $batch): StreamedResponse
    {
        $this->ensureCanAny(['attendance.view', 'batch.tab_attendance_report']);
        $this->assertBatchVisible($batch);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $report = $this->studentAttendance->batchMonthReport(
            $batch,
            (int) $validated['year'],
            (int) $validated['month']
        );

        $dayHeaders = [];
        for ($d = 1; $d <= $report['days_in_month']; $d++) {
            $dayHeaders[] = (string) $d;
        }

        $rows = $report['students']->map(function (array $student) use ($report) {
            $row = [$student['roll_no'], $student['name']];
            for ($d = 1; $d <= $report['days_in_month']; $d++) {
                $cell = $student['days'][$d] ?? null;
                $row[] = is_array($cell) ? ($cell['label'] ?? '') : '';
            }

            return $row;
        });

        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $batch->name) ?: 'batch';

        return CsvExport::download(
            $safe.'-attendance-'.sprintf('%04d-%02d', $report['year'], $report['month']).'.csv',
            array_merge(['Roll No', 'Name'], $dayHeaders),
            $rows
        );
    }

    public function teacherAttendance(Request $request, Teacher $teacher): StreamedResponse
    {
        $this->ensureCanAny(['teacher_attendance.show', 'teacher.tab_attendance']);
        $this->assertTeacherVisible($teacher);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $branch = $teacher->primaryBranch();
        $calendar = $this->staffAttendance->userMonthCalendar(
            (int) $teacher->user_id,
            (int) $teacher->institute_id,
            $branch ? (int) $branch->id : null,
            (int) $validated['year'],
            (int) $validated['month']
        );

        return $this->calendarCsv(
            preg_replace('/[^A-Za-z0-9_\-]+/', '_', $teacher->user?->name ?? 'teacher') ?: 'teacher',
            $calendar
        );
    }

    public function staffAttendance(Request $request, Staff $staff): StreamedResponse
    {
        $this->ensureCanAny(['teacher_attendance.show', 'staff.tab_attendance']);
        $this->assertStaffVisible($staff);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $branch = $staff->primaryBranch();
        $calendar = $this->staffAttendance->userMonthCalendar(
            (int) $staff->user_id,
            (int) $staff->institute_id,
            $branch ? (int) $branch->id : null,
            (int) $validated['year'],
            (int) $validated['month']
        );

        return $this->calendarCsv(
            preg_replace('/[^A-Za-z0-9_\-]+/', '_', $staff->user?->name ?? 'staff') ?: 'staff',
            $calendar
        );
    }

    public function teacherHomework(Teacher $teacher): StreamedResponse
    {
        $this->ensureCanAny(['homework.view', 'teacher.tab_assigned_work']);
        $this->assertTeacherVisible($teacher);

        $rows = $this->homeworkService->listForTeacher($teacher)->map(fn ($homework) => [
            $homework->title,
            $homework->batch?->name,
            $homework->subject?->name,
            $homework->homework_date?->format('Y-m-d'),
            $homework->due_date?->format('Y-m-d'),
            $homework->type,
        ]);

        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $teacher->user?->name ?? 'teacher') ?: 'teacher';

        return CsvExport::download($safe.'-homework.csv', [
            'Title', 'Batch', 'Subject', 'Date', 'Due Date', 'Type',
        ], $rows);
    }

    /**
     * @param  array{year: int, month: int, days: array<int, string>}  $calendar
     */
    protected function calendarCsv(string $safeName, array $calendar): StreamedResponse
    {
        $rows = [];
        foreach ($calendar['days'] as $day => $cls) {
            $rows[] = [
                sprintf('%04d-%02d-%02d', $calendar['year'], $calendar['month'], $day),
                $this->calendarStatusLabel($cls),
            ];
        }

        return CsvExport::download(
            $safeName.'-attendance-'.sprintf('%04d-%02d', $calendar['year'], $calendar['month']).'.csv',
            ['Date', 'Status'],
            $rows
        );
    }

    protected function calendarStatusLabel(?string $cls): string
    {
        return match ($cls) {
            'is-present' => 'Present',
            'is-absent' => 'Absent',
            'is-late' => 'Late',
            'is-holiday' => 'Holiday',
            'is-leave' => 'Leave',
            default => $cls ? (string) $cls : '',
        };
    }

    protected function assertTeacherVisible(Teacher $teacher): void
    {
        if ((int) $teacher->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Teacher not found.');
        }
        BranchScope::for($this->actor())->assertSharesBranchWith($teacher->user);
    }

    protected function assertStaffVisible(Staff $staff): void
    {
        if ((int) $staff->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Staff not found.');
        }
        BranchScope::for($this->actor())->assertSharesBranchWith($staff->user);
    }

    protected function assertStudentVisible(Student $student): void
    {
        $user = $this->actor();
        if ((int) $student->institute_id !== (int) $user->institute_id) {
            abort(404, 'Student not found.');
        }
        if ($user->isParent()) {
            \App\Support\ParentBatchScope::assertOwnsStudent($user, (int) $student->id);

            return;
        }
        if ($user->isTeacher()) {
            abort_unless(
                \App\Support\OwnBatchScope::studentIds($user)->contains((int) $student->id),
                404,
                'Student not found.'
            );

            return;
        }
        if ($student->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $student->branch_id);
        }
    }

    protected function assertBatchVisible(Batch $batch): void
    {
        $user = $this->actor();
        if ((int) $batch->institute_id !== (int) $user->institute_id) {
            abort(404, 'Batch not found.');
        }
        if ($user->isParent()) {
            \App\Support\ParentBatchScope::assertCanAccessBatch($user, (int) $batch->id);

            return;
        }
        if ($user->isTeacher()) {
            \App\Support\OwnBatchScope::assertCanAccessBatch($user, (int) $batch->id);

            return;
        }
        if ($batch->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $batch->branch_id);
        }
    }
}
