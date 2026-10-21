<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Services\LeaveRequestService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends ApiController
{
    public function __construct(
        protected LeaveRequestService $leaveService
    ) {}

    public function staffIndex(Request $request): JsonResponse
    {
        $this->ensureCanViewOrAdd('staff_leave');

        $query = $this->leaveQuery(LeaveRequest::TYPE_STAFF)
            ->with(['requester:id,name', 'branch:id,name', 'reviewer:id,name']);

        $this->applyLeaveFilters($query, $request);

        return $this->paginated(
            'OK',
            $query->latest('id')->paginate($this->perPage())
                ->through(fn (LeaveRequest $leave) => (new LeaveRequestResource($leave))->resolve($request))
        );
    }

    public function studentIndex(Request $request): JsonResponse
    {
        $this->ensureCanViewOrAdd('student_leave');

        $query = $this->leaveQuery(LeaveRequest::TYPE_STUDENT)
            ->with(['student:id,name,branch_id', 'requester:id,name', 'branch:id,name', 'reviewer:id,name']);

        $this->applyLeaveFilters($query, $request);

        return $this->paginated(
            'OK',
            $query->latest('id')->paginate($this->perPage())
                ->through(fn (LeaveRequest $leave) => (new LeaveRequestResource($leave))->resolve($request))
        );
    }

    public function storeStaff(Request $request): JsonResponse
    {
        $this->ensureCan('staff_leave.add');

        $validated = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $leave = $this->leaveService->createStaffLeave($this->actor(), $validated);

        return Ajax::success(
            'Leave request submitted.',
            (new LeaveRequestResource($leave->load(['requester:id,name', 'branch:id,name'])))->resolve($request),
            201
        );
    }

    public function storeStudent(Request $request): JsonResponse
    {
        $this->ensureCan('student_leave.add');

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);
        $this->assertCanApplyForStudent($student);

        $leave = $this->leaveService->createStudentLeave($this->actor(), $student, $validated);

        return Ajax::success(
            'Leave request submitted.',
            (new LeaveRequestResource($leave->load(['student:id,name', 'branch:id,name', 'requester:id,name'])))->resolve($request),
            201
        );
    }

    public function approve(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->assertLeavePermission($leaveRequest, 'approve');
        $this->assertVisible($leaveRequest);

        $leave = $this->leaveService->approve($leaveRequest);

        return Ajax::success('Leave approved.', (new LeaveRequestResource($leave))->resolve());
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->assertLeavePermission($leaveRequest, 'reject');
        $this->assertVisible($leaveRequest);

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $leave = $this->leaveService->reject($leaveRequest, $validated['review_note'] ?? null);

        return Ajax::success('Leave rejected.', (new LeaveRequestResource($leave))->resolve());
    }

    protected function applyLeaveFilters($query, Request $request): void
    {
        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('from_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('to_date', '<=', $request->input('end_date'));
        }
        if ($request->filled('student_id') && $this->actor()->isParent()) {
            $studentId = (int) $request->input('student_id');
            \App\Support\ParentBatchScope::assertOwnsStudent($this->actor(), $studentId);
            $query->where('student_id', $studentId);
        }
    }

    protected function leaveQuery(string $type)
    {
        $query = LeaveRequest::query()->where('requester_type', $type);
        $user = $this->actor();
        $module = $type === LeaveRequest::TYPE_STUDENT ? 'student_leave' : 'staff_leave';

        $query->where('institute_id', $user->institute_id);

        if ($user->isParent() || (! $user->can("{$module}.view") && $user->can("{$module}.add"))) {
            $query->where('requester_user_id', $user->id);
        }

        if ($user->isTeacher()) {
            if ($type === LeaveRequest::TYPE_STUDENT) {
                $ids = \App\Support\OwnBatchScope::studentIds($user);

                return $query->whereIn('student_id', $ids->isEmpty() ? [0] : $ids->all());
            }

            return $query;
        }

        if (! $user->isParent() && BranchScope::for($user)->isRestricted()) {
            $ids = BranchScope::for($user)->branchIds();
            $query->where(function ($q) use ($ids) {
                $q->whereIn('branch_id', $ids ?: [0])->orWhereNull('branch_id');
            });
        }

        return $query;
    }

    protected function assertCanApplyForStudent(Student $student): void
    {
        $user = $this->actor();

        if ((int) $student->institute_id !== (int) $user->institute_id) {
            abort(404, 'Student not found.');
        }

        if ($user->isParent()) {
            $owns = ParentGuardian::query()
                ->where('user_id', $user->id)
                ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
                ->exists();
            abort_unless($owns, 403, 'You can only apply leave for your own children.');

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

        if ($user->can('student_leave.view')) {
            if ($student->branch_id) {
                BranchScope::for($user)->assertCanAccessBranch((int) $student->branch_id);
            }

            return;
        }

        abort(403, 'Unauthorized.');
    }

    protected function assertLeavePermission(LeaveRequest $leave, string $action): void
    {
        $module = $leave->requester_type === LeaveRequest::TYPE_STUDENT ? 'student_leave' : 'staff_leave';
        $this->ensureCan("{$module}.{$action}");
    }

    protected function assertVisible(LeaveRequest $leave): void
    {
        $user = $this->actor();
        if ((int) $leave->institute_id !== (int) $user->institute_id) {
            abort(404, 'Leave request not found.');
        }

        if ($user->isParent() && (int) $leave->requester_user_id !== (int) $user->id) {
            abort(404, 'Leave request not found.');
        }

        $module = $leave->requester_type === LeaveRequest::TYPE_STUDENT ? 'student_leave' : 'staff_leave';
        if (! $user->isParent() && ! $user->can("{$module}.view") && $user->can("{$module}.add")) {
            if ((int) $leave->requester_user_id !== (int) $user->id) {
                abort(404, 'Leave request not found.');
            }
        }

        if ($user->isTeacher()) {
            if ($leave->requester_type === LeaveRequest::TYPE_STUDENT && $leave->student_id) {
                abort_unless(
                    \App\Support\OwnBatchScope::studentIds($user)->contains((int) $leave->student_id),
                    404,
                    'Leave request not found.'
                );
            }

            return;
        }

        if ($leave->branch_id && ! $user->isParent()) {
            BranchScope::for($user)->assertCanAccessBranch((int) $leave->branch_id);
        }
    }

    protected function ensureCanViewOrAdd(string $module): void
    {
        $user = $this->actor();
        if ($user->can("{$module}.view") || $user->can("{$module}.add") || $user->can("{$module}.show")) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
