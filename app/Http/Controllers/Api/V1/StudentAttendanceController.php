<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Batch;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Services\StudentAttendanceService;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Support\ParentBatchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentAttendanceController extends ApiController
{
    public function __construct(
        protected StudentAttendanceService $attendanceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCanAny(['attendance.view', 'attendance.add', 'attendance.edit']);

        if (! $request->filled('date')) {
            $request->merge(['date' => now()->toDateString()]);
        }

        $validated = $request->validate([
            'batch_id' => $this->assignedBatchIdRules(),
            'date' => ['required', 'date'],
        ]);

        $batch = Batch::query()->findOrFail((int) $validated['batch_id']);
        $this->assertBatchVisible($batch);

        $batch->load(['branch:id,name', 'standard:id,name']);

        return Ajax::success('Attendance loaded.', [
            'date' => $validated['date'],
            'batch' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'branch' => $batch->branch ? ['id' => $batch->branch->id, 'name' => $batch->branch->name] : null,
                'standard' => $batch->standard ? ['id' => $batch->standard->id, 'name' => $batch->standard->name] : null,
            ],
            'students' => $this->attendanceService->batchRoster($batch, $validated['date'])->all(),
            'meta' => $this->attendanceService->batchAttendanceMeta($batch, $validated['date']),
        ]);
    }

    public function store(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['attendance.add', 'attendance.edit']);
        $this->assertBatchVisible($batch);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.status' => ['required', Rule::in(array_keys(StudentAttendance::STATUSES))],
            'records.*.remark' => ['nullable', 'string', 'max:500'],
        ]);

        if ($error = $this->studentsNotInBatchError($batch, collect($validated['records'])->pluck('student_id')->all())) {
            return Ajax::error($error, null, 422);
        }

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

        return Ajax::success($message, [
            'meta' => $this->attendanceService->batchAttendanceMeta($batch, $validated['attendance_date']),
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

        return Ajax::success('Report loaded.', $this->attendanceService->batchMonthReport(
            $batch,
            (int) $validated['year'],
            (int) $validated['month']
        ));
    }

    public function studentReport(Request $request, Student $student): JsonResponse
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
            'batch_id' => $this->assignedBatchIdRules((int) $student->id),
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $batch = Batch::query()->findOrFail((int) $validated['batch_id']);
        $this->assertBatchVisible($batch);

        $enrolled = $batch->enrollments()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->exists();
        if (! $enrolled) {
            return Ajax::error('Student is not enrolled in this batch.', null, 422);
        }

        return Ajax::success('Attendance loaded.', $this->attendanceService->studentMonthCalendar(
            $student->id,
            $batch->id,
            (int) $validated['year'],
            (int) $validated['month']
        ));
    }

    /**
     * @param  array<int, int>  $studentIds
     */
    protected function studentsNotInBatchError(Batch $batch, array $studentIds): ?string
    {
        $allowed = $batch->enrollments()
            ->where('status', 'active')
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->all();

        return count(array_unique($studentIds)) !== count($allowed)
            ? 'One or more students are not enrolled in this batch.'
            : null;
    }

    protected function assertBatchVisible(Batch $batch): void
    {
        $user = $this->actor();
        if ((int) $batch->institute_id !== (int) $user->institute_id) {
            abort(404, 'Batch not found.');
        }

        if ($user->isTeacher()) {
            \App\Support\OwnBatchScope::assertCanAccessBatch($user, (int) $batch->id);

            return;
        }

        if ($user->isParent()) {
            ParentBatchScope::assertCanAccessBatch($user, (int) $batch->id);

            return;
        }

        if ($batch->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $batch->branch_id);
        }
    }

    protected function assertStudentVisible(Student $student): void
    {
        $user = $this->actor();
        if ((int) $student->institute_id !== (int) $user->institute_id) {
            abort(404, 'Student not found.');
        }

        if ($user->isParent()) {
            ParentBatchScope::assertOwnsStudent($user, (int) $student->id);

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
}
