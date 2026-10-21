<?php

namespace App\Support;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Teacher data boundary: batches assigned as class teacher (batch_teacher)
 * or subject teacher (batch_subject.teacher_user_id). Not BranchScope.
 */
class OwnBatchScope
{
    /**
     * @return list<int>
     */
    public static function batchIds(User $user, bool $activeOnly = false): array
    {
        if (! $user->isTeacher()) {
            return [];
        }

        $query = Batch::query()
            ->where('institute_id', $user->institute_id)
            ->where(fn ($q) => $q
                ->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id))
                ->orWhereHas('subjects', fn ($s) => $s->where('batch_subject.teacher_user_id', $user->id)));

        if ($activeOnly) {
            $query->where('status', 'active');
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Branch ids of the teacher's assigned batches.
     *
     * @return list<int>
     */
    public static function branchIds(User $user): array
    {
        $batchIds = self::batchIds($user);
        if ($batchIds === []) {
            return [];
        }

        return Batch::query()
            ->whereIn('id', $batchIds)
            ->whereNotNull('branch_id')
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function contains(User $user, int $batchId): bool
    {
        return in_array($batchId, self::batchIds($user), true);
    }

    public static function assertCanAccessBatch(User $user, int $batchId): void
    {
        abort_unless(
            self::contains($user, $batchId),
            404,
            'Batch not found.'
        );
    }

    /**
     * @return Collection<int, int>
     */
    public static function studentIds(User $user): Collection
    {
        $batchIds = self::batchIds($user);
        if ($batchIds === []) {
            return collect();
        }

        return \App\Models\StudentBatch::query()
            ->whereIn('batch_id', $batchIds)
            ->where('status', 'active')
            ->pluck('student_id')
            ->unique()
            ->values();
    }
}
