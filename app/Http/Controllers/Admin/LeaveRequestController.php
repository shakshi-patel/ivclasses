<?php

namespace App\Http\Controllers\Admin;

use App\Models\LeaveRequest;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Services\LeaveRequestService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends AdminController
{
    public function __construct(
        protected LeaveRequestService $leaveService
    ) {}

    public function staffIndex(Request $request): View
    {
        $this->ensureCanViewOrAdd('staff_leave');

        $query = $this->leaveQuery(LeaveRequest::TYPE_STAFF)
            ->with(['requester:id,name,phone', 'branch:id,name', 'reviewer:id,name']);

        $this->applyInstituteFilter($query, $request);

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

        $leaves = $query->latest('id')->get();
        $user = auth()->user();
        $filters = $this->listFilterVars($request, ['branches']);

        return view('admin.staff_leave_request.staff_leave_request', array_merge($filters, [
            'leaves' => $leaves,
            'canApply' => $user && ($user->isPlatformUser() || $user->can('staff_leave.add')),
            'canApprove' => $user && ($user->isPlatformUser() || $user->can('staff_leave.approve')),
            'canReject' => $user && ($user->isPlatformUser() || $user->can('staff_leave.reject')),
        ]));
    }

    public function studentIndex(Request $request): View
    {
        $this->ensureCanViewOrAdd('student_leave');

        $query = $this->leaveQuery(LeaveRequest::TYPE_STUDENT)
            ->with([
                'student:id,name,branch_id',
                'student.branch:id,name',
                'student.enrollments' => fn ($q) => $q->where('status', 'active')->with('batch:id,name'),
                'requester:id,name',
                'reviewer:id,name',
            ]);

        $this->applyInstituteFilter($query, $request);

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

        $leaves = $query->latest('id')->get();
        $user = auth()->user();
        $filters = $this->listFilterVars($request, ['branches']);

        return view('admin.student_leave_request.student_leave_request', array_merge($filters, [
            'leaves' => $leaves,
            'students' => $this->studentOptionsForApply($user),
            'canApply' => $user && ($user->isPlatformUser() || $user->can('student_leave.add')),
            'canShow' => $user && ($user->isPlatformUser() || $user->can('student_leave.show')),
            'canApprove' => $user && ($user->isPlatformUser() || $user->can('student_leave.approve')),
            'canReject' => $user && ($user->isPlatformUser() || $user->can('student_leave.reject')),
            'isParentApply' => $user && $user->isParent(),
        ]));
    }

    public function storeStaff(Request $request): JsonResponse
    {
        $this->ensureCan('staff_leave.add');

        $user = auth()->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $leave = $this->leaveService->createStaffLeave($user, $validated);

        return Ajax::success('Leave request submitted.', $leave->load(['requester', 'branch']), 201);
    }

    public function storeStudent(Request $request): JsonResponse
    {
        $this->ensureCan('student_leave.add');

        $user = auth()->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);
        $this->assertCanApplyForStudent($user, $student);

        $leave = $this->leaveService->createStudentLeave($user, $student, $validated);

        return Ajax::success('Leave request submitted.', $leave->load(['student', 'branch', 'requester']), 201);
    }

    public function approve(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->assertLeavePermission($leaveRequest, 'approve');
        $this->assertVisible($leaveRequest);

        $leave = $this->leaveService->approve($leaveRequest);

        return Ajax::success('Leave approved.', $leave);
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->assertLeavePermission($leaveRequest, 'reject');
        $this->assertVisible($leaveRequest);

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $leave = $this->leaveService->reject($leaveRequest, $validated['review_note'] ?? null);

        return Ajax::success('Leave rejected.', $leave);
    }

    protected function leaveQuery(string $type)
    {
        $query = LeaveRequest::query()->where('requester_type', $type);
        $user = auth()->user();
        $module = $type === LeaveRequest::TYPE_STUDENT ? 'student_leave' : 'staff_leave';

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if ($user && ($user->isParent() || (! $user->can("{$module}.view") && $user->can("{$module}.add")))) {
            $query->where('requester_user_id', $user->id);
        }

        if ($user && ! $user->isParent() && BranchScope::for($user)->isRestricted()) {
            $ids = BranchScope::for($user)->branchIds();
            $query->where(function ($q) use ($ids) {
                $q->whereIn('branch_id', $ids)->orWhereNull('branch_id');
            });
        }

        return $query;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Student>
     */
    protected function studentOptionsForApply($user)
    {
        if (! $user) {
            return collect();
        }

        if ($user->isParent()) {
            return Student::query()
                ->where('status', 'active')
                ->whereHas('parent', fn ($q) => $q->where('user_id', $user->id))
                ->orderBy('name')
                ->get(['id', 'name', 'branch_id']);
        }

        if ($user->isPlatformUser() || $user->can('student_leave.view')) {
            $query = Student::query()
                ->where('status', 'active')
                ->orderBy('name');

            if (! $user->isPlatformUser()) {
                $query->where('institute_id', $user->institute_id);
            }

            if (BranchScope::for($user)->isRestricted()) {
                $query->whereIn('branch_id', BranchScope::for($user)->branchIds());
            }

            return $query->get(['id', 'name', 'branch_id']);
        }

        return collect();
    }

    protected function assertCanApplyForStudent($user, Student $student): void
    {
        if ($user->isPlatformUser()) {
            return;
        }

        if ((int) $student->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($user->isParent()) {
            $owns = ParentGuardian::query()
                ->where('user_id', $user->id)
                ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
                ->exists();

            abort_unless($owns, 403, 'You can only apply leave for your own children.');

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
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $leave->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($user && $user->isParent() && (int) $leave->requester_user_id !== (int) $user->id) {
            abort(404);
        }

        $module = $leave->requester_type === LeaveRequest::TYPE_STUDENT ? 'student_leave' : 'staff_leave';
        if ($user && ! $user->isPlatformUser() && ! $user->can("{$module}.view") && $user->can("{$module}.add")) {
            if ((int) $leave->requester_user_id !== (int) $user->id) {
                abort(404);
            }
        }

        if ($leave->branch_id && $user && ! $user->isParent()) {
            BranchScope::for($user)->assertCanAccessBranch((int) $leave->branch_id);
        }
    }

    protected function ensureCanViewOrAdd(string $module): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can("{$module}.view") || $user->can("{$module}.add"))) {
            return;
        }

        abort(403, 'Unauthorized.');
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
