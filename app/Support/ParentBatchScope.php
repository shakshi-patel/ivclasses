<?php

namespace App\Support;

use App\Models\Student;
use App\Models\StudentBatch;
use App\Models\User;
use Illuminate\Support\Collection;

class ParentBatchScope
{
    /**
     * @return Collection<int, Student>
     */
    public static function studentOptions(User $user): Collection
    {
        if (! $user->isParent()) {
            return collect();
        }

        return Student::query()
            ->where('status', 'active')
            ->whereHas('parent', fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, int>
     */
    public static function batchIds(User $user, ?int $studentId = null): Collection
    {
        $studentIds = self::studentIds($user, $studentId);

        if ($studentIds->isEmpty()) {
            return collect();
        }

        return StudentBatch::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'active')
            ->pluck('batch_id')
            ->unique()
            ->values();
    }

    public static function assertCanAccessBatch(User $user, int $batchId): void
    {
        abort_unless(
            self::batchIds($user)->contains($batchId),
            403,
            'You cannot access content for this batch.'
        );
    }

    public static function ownsStudent(User $user, int $studentId): bool
    {
        if (! $user->isParent()) {
            return false;
        }

        return Student::query()
            ->where('id', $studentId)
            ->whereHas('parent', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public static function assertOwnsStudent(User $user, int $studentId): void
    {
        abort_unless(
            self::ownsStudent($user, $studentId),
            404,
            'Student not found.'
        );
    }

    /**
     * @return Collection<int, int>
     */
    public static function studentIds(User $user, ?int $studentId = null): Collection
    {
        if ($studentId) {
            abort_unless(
                self::ownsStudent($user, $studentId),
                403,
                'You can only view content for your own children.'
            );

            return collect([(int) $studentId]);
        }

        return Student::query()
            ->where('status', 'active')
            ->whereHas('parent', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');
    }
}
