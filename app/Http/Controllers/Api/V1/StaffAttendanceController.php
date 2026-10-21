<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Teacher;
use App\Services\StaffAttendanceService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StaffAttendanceController extends ApiController
{
    public function __construct(
        protected StaffAttendanceService $attendanceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCanAny(['teacher_attendance.view', 'teacher_attendance.add', 'teacher_attendance.edit']);

        if (! $request->filled('date')) {
            $request->merge(['date' => now()->toDateString()]);
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $instituteId = (int) $this->actor()->institute_id;
        $branchId = $this->resolveBranchId($request);

        $roster = $this->scopedRoster(
            $this->attendanceService->roster($instituteId, $validated['date'], $branchId),
            $branchId
        );

        return Ajax::success('Attendance loaded.', [
            'date' => $validated['date'],
            'branch_id' => $branchId,
            'staff' => $roster->all(),
            'meta' => $this->attendanceService->attendanceMeta($instituteId, $validated['date'], $branchId),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCanAny(['teacher_attendance.add', 'teacher_attendance.edit']);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['required', Rule::in(array_keys(StaffAttendance::STATUSES))],
            'records.*.remark' => ['nullable', 'string', 'max:500'],
        ]);

        $instituteId = (int) $this->actor()->institute_id;
        $branchId = $this->resolveBranchId($request);

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

        return Ajax::success($message, [
            'meta' => $this->attendanceService->attendanceMeta($instituteId, $validated['attendance_date'], $branchId),
        ]);
    }

    public function teacherReport(Request $request, Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher_attendance.view', 'teacher_attendance.show', 'teacher.tab_attendance']);
        $this->assertTeacherVisible($teacher);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $branchId = $teacher->primaryBranch()?->id;

        return Ajax::success('Attendance loaded.', $this->attendanceService->userMonthCalendar(
            (int) $teacher->user_id,
            (int) $teacher->institute_id,
            $branchId ? (int) $branchId : null,
            (int) $validated['year'],
            (int) $validated['month']
        ));
    }

    public function staffReport(Request $request, Staff $staff): JsonResponse
    {
        $this->ensureCanAny(['teacher_attendance.view', 'teacher_attendance.show', 'staff.tab_attendance']);
        $this->assertStaffVisible($staff);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $branchId = $staff->primaryBranch()?->id;

        return Ajax::success('Attendance loaded.', $this->attendanceService->userMonthCalendar(
            (int) $staff->user_id,
            (int) $staff->institute_id,
            $branchId ? (int) $branchId : null,
            (int) $validated['year'],
            (int) $validated['month']
        ));
    }

    protected function resolveBranchId(Request $request): ?int
    {
        $scope = BranchScope::for($this->actor());

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            $scope->assertCanAccessBranch($branchId);

            return $branchId;
        }

        if ($scope->isRestricted()) {
            $ids = $scope->branchIds();

            return count($ids) === 1 ? (int) $ids[0] : null;
        }

        return null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $roster
     * @return Collection<int, array<string, mixed>>
     */
    protected function scopedRoster(Collection $roster, ?int $branchId): Collection
    {
        if ($branchId !== null) {
            return $roster;
        }

        $scope = BranchScope::for($this->actor());
        if (! $scope->isRestricted()) {
            return $roster;
        }

        $ids = $scope->branchIds();

        return $roster
            ->filter(fn (array $row) => in_array((int) ($row['branch_id'] ?? 0), $ids, true))
            ->values();
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
}
