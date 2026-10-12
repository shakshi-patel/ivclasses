<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Homework;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\BranchScope;
use App\Support\ParentBatchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomeworkService
{
    /**
     * @return Builder<Homework>
     */
    public function queryForUser(?User $user = null, ?int $studentId = null): Builder
    {
        $user = $user ?? auth()->user();
        $query = Homework::query();

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if ($user && $user->isParent()) {
            $batchIds = ParentBatchScope::batchIds($user, $studentId);

            return $batchIds->isEmpty()
                ? $query->whereRaw('0 = 1')
                : $query->whereIn('batch_id', $batchIds);
        }

        if ($user && BranchScope::for($user)->isRestricted()) {
            $ids = BranchScope::for($user)->branchIds();
            $query->whereIn('branch_id', $ids);
        }

        if ($user && $user->isTeacher()) {
            $query->where(function ($q) use ($user) {
                $q->where('teacher_user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $query;
    }

    /**
     * @return Collection<int, Homework>
     */
    public function listForUser(?User $user = null, ?int $studentId = null): Collection
    {
        return $this->queryForUser($user, $studentId)
            ->with(['batch:id,name', 'subject:id,name', 'creator:id,name', 'branch:id,name'])
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, Homework>
     */
    public function listForStudent(Student $student): Collection
    {
        $batchIds = $student->enrollments()
            ->where('status', 'active')
            ->pluck('batch_id')
            ->unique()
            ->filter()
            ->values();

        if ($batchIds->isEmpty()) {
            return collect();
        }

        return Homework::query()
            ->where('institute_id', $student->institute_id)
            ->whereIn('batch_id', $batchIds)
            ->with(['batch:id,name', 'subject:id,name', 'creator:id,name'])
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Homework assigned by / to this teacher.
     *
     * @return Collection<int, Homework>
     */
    public function listForTeacher(Teacher $teacher): Collection
    {
        if (! $teacher->user_id) {
            return collect();
        }

        return $this->teacherHomeworkQuery($teacher)->get();
    }

    public function paginateForTeacher(Teacher $teacher, int $perPage = 10, string $pageName = 'work_page'): LengthAwarePaginator
    {
        if (! $teacher->user_id) {
            return new LengthAwarePaginator([], 0, $perPage, LengthAwarePaginator::resolveCurrentPage($pageName), [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]);
        }

        return $this->teacherHomeworkQuery($teacher)
            ->paginate($perPage, ['*'], $pageName);
    }

    /**
     * @return Builder<Homework>
     */
    protected function teacherHomeworkQuery(Teacher $teacher): Builder
    {
        return Homework::query()
            ->where('institute_id', $teacher->institute_id)
            ->where(function ($q) use ($teacher) {
                $q->where('teacher_user_id', $teacher->user_id)
                    ->orWhere('created_by', $teacher->user_id);
            })
            ->with(['batch:id,name', 'subject:id,name', 'branch:id,name'])
            ->orderByDesc('due_date')
            ->orderByDesc('id');
    }

    /**
     * @return Collection<int, Student>
     */
    public function parentStudentOptions(User $user): Collection
    {
        return ParentBatchScope::studentOptions($user);
    }

    public function assertUserCanAccessHomework(User $user, Homework $homework): void
    {
        if ($user && ! $user->isPlatformUser() && (int) $homework->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($user && $user->isParent()) {
            ParentBatchScope::assertCanAccessBatch($user, (int) $homework->batch_id);

            return;
        }

        if ($homework->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $homework->branch_id);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $attachment = null): Homework
    {
        return DB::transaction(function () use ($data, $attachment) {
            $batch = Batch::query()->findOrFail((int) $data['batch_id']);

            $homework = Homework::query()->create([
                'institute_id' => (int) $batch->institute_id,
                'branch_id' => $batch->branch_id ? (int) $batch->branch_id : null,
                'batch_id' => $batch->id,
                'subject_id' => $data['subject_id'] ?? null,
                'teacher_user_id' => $data['teacher_user_id'] ?? auth()->id(),
                'title' => $data['title'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'homework_date' => $data['homework_date'],
                'due_date' => $data['due_date'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            if ($attachment) {
                $homework->update([
                    'attachment_path' => $this->storeAttachment($attachment, (int) $batch->institute_id),
                ]);
            }

            return $homework->fresh(['batch', 'subject', 'teacher', 'creator', 'branch']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Homework $homework, array $data, ?UploadedFile $attachment = null, bool $removeAttachment = false): Homework
    {
        return DB::transaction(function () use ($homework, $data, $attachment, $removeAttachment) {
            $batchId = (int) ($data['batch_id'] ?? $homework->batch_id);
            $batch = Batch::query()->findOrFail($batchId);

            $homework->update([
                'institute_id' => (int) $batch->institute_id,
                'branch_id' => $batch->branch_id ? (int) $batch->branch_id : null,
                'batch_id' => $batch->id,
                'subject_id' => $data['subject_id'] ?? null,
                'teacher_user_id' => $data['teacher_user_id'] ?? $homework->teacher_user_id,
                'title' => $data['title'] ?? $homework->title,
                'type' => $data['type'] ?? $homework->type,
                'description' => $data['description'] ?? $homework->description,
                'homework_date' => $data['homework_date'] ?? $homework->homework_date,
                'due_date' => $data['due_date'] ?? $homework->due_date,
                'updated_by' => auth()->id(),
            ]);

            if ($removeAttachment && $homework->attachment_path) {
                $this->deleteAttachment($homework->attachment_path);
                $homework->update(['attachment_path' => null]);
            }

            if ($attachment) {
                if ($homework->attachment_path) {
                    $this->deleteAttachment($homework->attachment_path);
                }
                $homework->update([
                    'attachment_path' => $this->storeAttachment($attachment, (int) $batch->institute_id),
                ]);
            }

            return $homework->fresh(['batch', 'subject', 'teacher', 'creator', 'branch']);
        });
    }

    public function delete(Homework $homework): void
    {
        if ($homework->attachment_path) {
            $this->deleteAttachment($homework->attachment_path);
        }

        $homework->delete();
    }

    protected function storeAttachment(UploadedFile $file, int $instituteId): string
    {
        return $file->store("homework/{$instituteId}", 'public');
    }

    protected function deleteAttachment(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
