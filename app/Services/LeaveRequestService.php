<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    /**
     * @param  array{from_date: string, to_date: string, reason?: string|null}  $data
     */
    public function createStaffLeave(User $user, array $data): LeaveRequest
    {
        $this->assertNoOverlappingPending(
            LeaveRequest::TYPE_STAFF,
            $user->id,
            null,
            $data['from_date'],
            $data['to_date']
        );

        return DB::transaction(function () use ($user, $data) {
            return LeaveRequest::query()->create([
                'institute_id' => (int) $user->institute_id,
                'branch_id' => $this->resolveUserBranchId($user),
                'requester_type' => LeaveRequest::TYPE_STAFF,
                'requester_user_id' => $user->id,
                'student_id' => null,
                'from_date' => $data['from_date'],
                'to_date' => $data['to_date'],
                'reason' => $data['reason'] ?? null,
                'status' => LeaveRequest::STATUS_PENDING,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    /**
     * @param  array{from_date: string, to_date: string, reason?: string|null}  $data
     */
    public function createStudentLeave(User $user, Student $student, array $data): LeaveRequest
    {
        $this->assertNoOverlappingPending(
            LeaveRequest::TYPE_STUDENT,
            $user->id,
            $student->id,
            $data['from_date'],
            $data['to_date']
        );

        return DB::transaction(function () use ($user, $student, $data) {
            return LeaveRequest::query()->create([
                'institute_id' => (int) $student->institute_id,
                'branch_id' => $student->branch_id ? (int) $student->branch_id : null,
                'requester_type' => LeaveRequest::TYPE_STUDENT,
                'requester_user_id' => $user->id,
                'student_id' => $student->id,
                'from_date' => $data['from_date'],
                'to_date' => $data['to_date'],
                'reason' => $data['reason'] ?? null,
                'status' => LeaveRequest::STATUS_PENDING,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    public function approve(LeaveRequest $leave, ?string $note = null): LeaveRequest
    {
        return DB::transaction(function () use ($leave, $note) {
            $leave->update([
                'status' => LeaveRequest::STATUS_APPROVED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $note,
                'updated_by' => auth()->id(),
            ]);

            return $leave->fresh(['requester', 'student', 'branch', 'reviewer']);
        });
    }

    public function reject(LeaveRequest $leave, ?string $note = null): LeaveRequest
    {
        return DB::transaction(function () use ($leave, $note) {
            $leave->update([
                'status' => LeaveRequest::STATUS_REJECTED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $note,
                'updated_by' => auth()->id(),
            ]);

            return $leave->fresh(['requester', 'student', 'branch', 'reviewer']);
        });
    }

    public function delete(LeaveRequest $leave): void
    {
        $leave->delete();
    }

    protected function resolveUserBranchId(User $user): ?int
    {
        $branch = $user->branches()->wherePivot('is_primary', true)->first()
            ?? $user->branches()->first();

        return $branch?->id;
    }

    protected function assertNoOverlappingPending(
        string $type,
        int $requesterUserId,
        ?int $studentId,
        string $fromDate,
        string $toDate
    ): void {
        $query = LeaveRequest::query()
            ->where('requester_type', $type)
            ->where('requester_user_id', $requesterUserId)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->whereDate('from_date', '<=', $toDate)
            ->whereDate('to_date', '>=', $fromDate);

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'from_date' => ['A pending leave request already exists for this period.'],
            ]);
        }
    }
}
