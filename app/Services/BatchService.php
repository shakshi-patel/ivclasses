<?php

namespace App\Services;

use App\Models\Batch;
use Illuminate\Support\Arr;

class BatchService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $subjects
     * @param  array<int, int>  $teacherIds  Teachers assigned to the batch directly
     *                                       (independent of any subject row).
     */
    public function create(array $data, array $subjects = [], array $teacherIds = []): Batch
    {
        $batch = Batch::query()->create($data);
        $this->syncSubjects($batch, $subjects, $teacherIds);

        return $batch->fresh(['branch', 'standard', 'subjects', 'teachers']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $subjects
     * @param  array<int, int>  $teacherIds  Teachers assigned to the batch directly
     *                                       (independent of any subject row).
     */
    public function update(Batch $batch, array $data, array $subjects = [], array $teacherIds = []): Batch
    {
        $batch->update($data);
        $this->syncSubjects($batch, $subjects, $teacherIds);

        return $batch->fresh(['branch', 'standard', 'subjects', 'teachers']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $subjects
     * @param  array<int, int>  $teacherIds
     */
    protected function syncSubjects(Batch $batch, array $subjects, array $teacherIds = []): void
    {
        $sync = [];
        $teacherFirstSeen = [];

        foreach ($subjects as $index => $row) {
            $subjectId = (int) Arr::get($row, 'subject_id', 0);
            if ($subjectId < 1) {
                continue;
            }

            $teacherUserId = Arr::get($row, 'teacher_user_id');
            $teacherUserId = $teacherUserId ? (int) $teacherUserId : null;

            $sync[$subjectId] = [
                'teacher_user_id' => $teacherUserId,
                'fees_amount' => Arr::get($row, 'fees_amount'),
            ];

            if ($teacherUserId) {
                $teacherFirstSeen[$teacherUserId] = $index === 0;
            }
        }

        $batch->subjects()->sync($sync);

        // The batch's teacher roster is the union of subject-linked teachers and
        // whatever was explicitly picked in the "Assign Teachers" field — a full
        // sync() here is safe (not destructive) because that field always starts
        // pre-filled with the batch's current teachers, so a plain re-save keeps them.
        foreach ($teacherIds as $teacherUserId) {
            if (! array_key_exists($teacherUserId, $teacherFirstSeen)) {
                $teacherFirstSeen[$teacherUserId] = false;
            }
        }

        $teacherSync = [];
        $primarySet = false;
        foreach ($teacherFirstSeen as $teacherUserId => $isFirst) {
            $isPrimary = ! $primarySet && $isFirst;
            $teacherSync[$teacherUserId] = ['is_primary' => $isPrimary];
            if ($isPrimary) {
                $primarySet = true;
            }
        }

        if (! $primarySet && ! empty($teacherSync)) {
            $firstId = array_key_first($teacherSync);
            $teacherSync[$firstId]['is_primary'] = true;
        }

        $batch->teachers()->sync($teacherSync);
    }
}
