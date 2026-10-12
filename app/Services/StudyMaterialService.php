<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Student;
use App\Models\StudyMaterial;
use App\Models\User;
use App\Support\BranchScope;
use App\Support\ParentBatchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudyMaterialService
{
    /**
     * @return Builder<StudyMaterial>
     */
    public function queryForUser(?User $user = null, ?int $studentId = null): Builder
    {
        $user = $user ?? auth()->user();
        $query = StudyMaterial::query();

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
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    /**
     * @return Collection<int, StudyMaterial>
     */
    public function listForUser(?User $user = null, ?int $studentId = null): Collection
    {
        return $this->queryForUser($user, $studentId)
            ->with(['batch:id,name', 'subject:id,name', 'creator:id,name', 'branch:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, StudyMaterial>
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

        return StudyMaterial::query()
            ->where('institute_id', $student->institute_id)
            ->whereIn('batch_id', $batchIds)
            ->with(['batch:id,name', 'subject:id,name', 'creator:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function assertUserCanAccess(User $user, StudyMaterial $studyMaterial): void
    {
        if ($user && ! $user->isPlatformUser() && (int) $studyMaterial->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($user && $user->isParent()) {
            ParentBatchScope::assertCanAccessBatch($user, (int) $studyMaterial->batch_id);

            return;
        }

        if ($studyMaterial->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $studyMaterial->branch_id);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UploadedFile $file): StudyMaterial
    {
        return DB::transaction(function () use ($data, $file) {
            $batch = Batch::query()->findOrFail((int) $data['batch_id']);

            $studyMaterial = StudyMaterial::query()->create([
                'institute_id' => (int) $batch->institute_id,
                'branch_id' => $batch->branch_id ? (int) $batch->branch_id : null,
                'batch_id' => $batch->id,
                'subject_id' => $data['subject_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'file_path' => $this->storeFile($file, (int) $batch->institute_id),
                'file_name' => $file->getClientOriginalName(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            return $studyMaterial->fresh(['batch', 'subject', 'creator', 'branch']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(StudyMaterial $studyMaterial, array $data, ?UploadedFile $file = null, bool $removeFile = false): StudyMaterial
    {
        return DB::transaction(function () use ($studyMaterial, $data, $file, $removeFile) {
            $batchId = (int) ($data['batch_id'] ?? $studyMaterial->batch_id);
            $batch = Batch::query()->findOrFail($batchId);

            $studyMaterial->update([
                'institute_id' => (int) $batch->institute_id,
                'branch_id' => $batch->branch_id ? (int) $batch->branch_id : null,
                'batch_id' => $batch->id,
                'subject_id' => $data['subject_id'] ?? null,
                'title' => $data['title'] ?? $studyMaterial->title,
                'description' => $data['description'] ?? $studyMaterial->description,
                'updated_by' => auth()->id(),
            ]);

            if ($removeFile && $studyMaterial->file_path) {
                $this->deleteFile($studyMaterial->file_path);
                $studyMaterial->update(['file_path' => null, 'file_name' => null]);
            }

            if ($file) {
                if ($studyMaterial->file_path) {
                    $this->deleteFile($studyMaterial->file_path);
                }
                $studyMaterial->update([
                    'file_path' => $this->storeFile($file, (int) $batch->institute_id),
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }

            return $studyMaterial->fresh(['batch', 'subject', 'creator', 'branch']);
        });
    }

    public function delete(StudyMaterial $studyMaterial): void
    {
        if ($studyMaterial->file_path) {
            $this->deleteFile($studyMaterial->file_path);
        }

        $studyMaterial->delete();
    }

    protected function storeFile(UploadedFile $file, int $instituteId): string
    {
        return $file->store("study-material/{$instituteId}", 'public');
    }

    protected function deleteFile(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
