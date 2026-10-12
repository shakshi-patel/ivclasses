<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Student;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Services\StudentAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentAttendanceController extends AdminController
{
    public function __construct(
        protected StudentAttendanceService $attendanceService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('attendance.view');

        $filters = $this->listFilterVars($request, ['branches', 'batches']);

        $query = $this->batchQuery()
            ->with(['branch:id,name'])
            ->withCount(['enrollments as active_students_count' => fn ($q) => $q->where('status', 'active')])
            ->where('status', 'active');

        $this->applyInstituteFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('batch_id')) {
            $query->whereKey((int) $request->input('batch_id'));
        }

        $batchList = $query->orderBy('name')->get();

        return view('admin.attendance.student_attendance', array_merge(compact('batchList'), $filters));
    }

    public function mark(Request $request, Batch $batch): View
    {
        $this->ensureCanAny(['attendance.add', 'attendance.edit']);
        $this->assertBatchVisible($batch);

        $date = $request->input('date', now()->toDateString());
        $roster = $this->attendanceService->batchRoster($batch, $date);
        $attendanceMeta = $this->attendanceService->batchAttendanceMeta($batch, $date);

        $batch->load(['branch:id,name', 'standard:id,name']);

        return view('admin.attendance.mark_student_attendance', compact('batch', 'date', 'roster', 'attendanceMeta'));
    }

    public function data(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['attendance.add', 'attendance.edit', 'batch.tab_attendance']);
        $this->assertBatchVisible($batch);

        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $roster = $this->attendanceService->batchRoster($batch, $validated['date']);
        $meta = $this->attendanceService->batchAttendanceMeta($batch, $validated['date']);

        return Ajax::success('Attendance loaded.', [
            'date' => $validated['date'],
            'students' => $roster,
            'meta' => $meta,
        ]);
    }

    public function batchReport(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['attendance.view', 'batch.tab_attendance_report']);
        $this->assertBatchVisible($batch);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $report = $this->attendanceService->batchMonthReport(
            $batch,
            (int) $validated['year'],
            (int) $validated['month']
        );

        return Ajax::success('Report loaded.', $report);
    }

    public function studentReport(Request $request, Student $student): JsonResponse
    {
        $this->ensureCanAny(['attendance.view', 'student.show']);
        $this->assertStudentVisible($student);

        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $batch = Batch::query()->findOrFail((int) $validated['batch_id']);
        $this->assertBatchVisible($batch);
        $this->assertStudentInBatch($student, $batch);

        $calendar = $this->attendanceService->studentMonthCalendar(
            $student->id,
            $batch->id,
            (int) $validated['year'],
            (int) $validated['month']
        );

        return Ajax::success('Attendance loaded.', $calendar);
    }

    public function store(Request $request, Batch $batch): JsonResponse|RedirectResponse
    {
        $this->ensureCanAny(['attendance.add', 'attendance.edit']);
        $this->assertBatchVisible($batch);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.status' => ['required', Rule::in(array_keys(\App\Models\StudentAttendance::STATUSES))],
            'records.*.remark' => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertStudentsInBatch($batch, collect($validated['records'])->pluck('student_id')->all());

        $isUpdate = $this->attendanceService->batchAttendanceMeta($batch, $validated['attendance_date'])['is_marked'];
        $this->ensureCan($isUpdate ? 'attendance.edit' : 'attendance.add');

        $count = $this->attendanceService->markBatch(
            $batch,
            $validated['attendance_date'],
            $validated['records']
        );

        $message = $isUpdate
            ? "Attendance updated for {$count} student(s)."
            : "Attendance saved for {$count} student(s).";

        if ($request->expectsJson()) {
            return Ajax::success($message, [
                'meta' => $this->attendanceService->batchAttendanceMeta($batch, $validated['attendance_date']),
            ]);
        }

        return redirect()
            ->route('admin.attendance.student.mark', ['batch' => $batch, 'date' => $validated['attendance_date']])
            ->with('success', $message);
    }

    protected function batchQuery()
    {
        $query = Batch::query();
        $user = auth()->user();

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if (BranchScope::for()->isRestricted()) {
            $ids = BranchScope::for()->branchIds();
            $query->whereIn('branch_id', $ids);
        }

        return $query;
    }

    /**
     * @param  array<int, int>  $studentIds
     */
    protected function assertStudentsInBatch(Batch $batch, array $studentIds): void
    {
        $allowed = $batch->enrollments()
            ->where('status', 'active')
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->all();

        if (count(array_unique($studentIds)) !== count($allowed)) {
            abort(422, 'One or more students are not enrolled in this batch.');
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

    protected function assertStudentInBatch(Student $student, Batch $batch): void
    {
        $exists = $batch->enrollments()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($exists, 422, 'Student is not enrolled in this batch.');
    }
}
