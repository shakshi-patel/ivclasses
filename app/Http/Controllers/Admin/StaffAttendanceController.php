<?php

namespace App\Http\Controllers\Admin;

use App\Models\Staff;
use App\Models\Teacher;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Services\StaffAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffAttendanceController extends AdminController
{
    public function __construct(
        protected StaffAttendanceService $attendanceService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('teacher_attendance.view');

        $filters = $this->listFilterVars($request, ['branches']);
        $instituteId = $this->filterInstituteId($request);
        $date = $request->input('date', now()->toDateString());
        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        if (! $instituteId) {
            return view('admin.attendance.teacher_attendance', array_merge($filters, [
                'roster' => collect(),
                'meta' => ['is_marked' => false, 'marked_count' => 0, 'total_staff' => 0],
                'date' => $date,
                'branchId' => $branchId,
                'instituteId' => null,
            ]));
        }

        $roster = $this->attendanceService->roster($instituteId, $date, $branchId);
        $meta = $this->attendanceService->attendanceMeta($instituteId, $date, $branchId);

        return view('admin.attendance.teacher_attendance', array_merge($filters, [
            'roster' => $roster,
            'meta' => $meta,
            'date' => $date,
            'branchId' => $branchId,
            'instituteId' => $instituteId,
        ]));
    }

    public function mark(Request $request): View
    {
        $this->ensureCanAny(['teacher_attendance.add', 'teacher_attendance.edit']);

        $instituteId = $this->filterInstituteId($request);
        abort_unless($instituteId, 403, 'Institute is required.');

        $date = $request->input('date', now()->toDateString());
        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $roster = $this->attendanceService->roster($instituteId, $date, $branchId);
        $meta = $this->attendanceService->attendanceMeta($instituteId, $date, $branchId);
        $branches = BranchScope::for()->branchesForInstitute($instituteId);

        return view('admin.attendance.mark_staff_attendance', compact('roster', 'meta', 'date', 'branchId', 'branches', 'instituteId'));
    }

    public function data(Request $request): JsonResponse
    {
        $this->ensureCanAny(['teacher_attendance.view', 'teacher_attendance.add', 'teacher_attendance.edit']);

        $instituteId = $this->filterInstituteId($request);
        abort_unless($instituteId, 403, 'Institute is required.');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        return Ajax::success('Attendance loaded.', [
            'date' => $validated['date'],
            'staff' => $this->attendanceService->roster($instituteId, $validated['date'], $branchId),
            'meta' => $this->attendanceService->attendanceMeta($instituteId, $validated['date'], $branchId),
        ]);
    }

    public function teacherReport(Request $request, Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher_attendance.show', 'teacher.tab_attendance']);
        $this->assertTeacherVisible($teacher);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $branchId = $teacher->primaryBranch()?->id;

        $calendar = $this->attendanceService->userMonthCalendar(
            (int) $teacher->user_id,
            (int) $teacher->institute_id,
            $branchId ? (int) $branchId : null,
            (int) $validated['year'],
            (int) $validated['month']
        );

        return Ajax::success('Attendance loaded.', $calendar);
    }

    public function staffReport(Request $request, Staff $staff): JsonResponse
    {
        $this->ensureCanAny(['teacher_attendance.show', 'staff.tab_attendance']);
        $this->assertStaffVisible($staff);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $branchId = $staff->primaryBranch()?->id;

        $calendar = $this->attendanceService->userMonthCalendar(
            (int) $staff->user_id,
            (int) $staff->institute_id,
            $branchId ? (int) $branchId : null,
            (int) $validated['year'],
            (int) $validated['month']
        );

        return Ajax::success('Attendance loaded.', $calendar);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCanAny(['teacher_attendance.add', 'teacher_attendance.edit']);

        $instituteId = $this->filterInstituteId($request);
        abort_unless($instituteId, 403, 'Institute is required.');

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['required', Rule::in(array_keys(\App\Models\StaffAttendance::STATUSES))],
            'records.*.remark' => ['nullable', 'string', 'max:500'],
        ]);

        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        $isUpdate = $this->attendanceService->attendanceMeta($instituteId, $validated['attendance_date'], $branchId)['is_marked'];
        $this->ensureCan($isUpdate ? 'teacher_attendance.edit' : 'teacher_attendance.add');

        $count = $this->attendanceService->mark(
            $instituteId,
            $validated['attendance_date'],
            $validated['records'],
            $branchId
        );

        $message = $isUpdate
            ? "Attendance updated for {$count} staff member(s)."
            : "Attendance saved for {$count} staff member(s).";

        if ($request->expectsJson()) {
            return Ajax::success($message, [
                'meta' => $this->attendanceService->attendanceMeta($instituteId, $validated['attendance_date'], $branchId),
            ]);
        }

        return redirect()
            ->route('admin.attendance.teacher.mark', [
                'date' => $validated['attendance_date'],
                'branch_id' => $branchId,
                'institute_id' => $instituteId,
            ])
            ->with('success', $message);
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
}
