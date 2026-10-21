<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Notice;
use App\Models\Student;
use App\Models\User;
use App\Support\BranchScope;
use App\Support\OwnBatchScope;
use App\Support\ParentBatchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NoticeService
{
    /**
     * @return Builder<Notice>
     */
    public function queryForUser(?User $user = null, ?int $studentId = null): Builder
    {
        $user = $user ?? auth()->user();
        $query = Notice::query()->where('status', 'active');

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if ($user && $user->isParent()) {
            $batchIds = ParentBatchScope::batchIds($user, $studentId);

            return $query->where(function ($q) use ($batchIds) {
                $q->whereIn('audience', [Notice::AUDIENCE_ALL, Notice::AUDIENCE_PARENTS])
                    ->where(function ($inner) use ($batchIds) {
                        $inner->whereDoesntHave('batches');
                        if ($batchIds->isNotEmpty()) {
                            $inner->orWhereHas('batches', fn ($bq) => $bq->whereIn('batches.id', $batchIds));
                        }
                    });
            });
        }

        if ($user && $user->isTeacher()) {
            return $this->scopeTeacherNotices($query, $user);
        }

        if ($user && BranchScope::for($user)->isRestricted()) {
            $ids = BranchScope::for($user)->branchIds();
            $query->where(function ($q) use ($ids) {
                $q->whereDoesntHave('branches')
                    ->orWhereHas('branches', fn ($bq) => $bq->whereIn('branches.id', $ids));
            });
        }

        return $query;
    }

    /**
     * @return Builder<Notice>
     */
    public function adminQueryForUser(?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        $query = Notice::query();

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if ($user && $user->isTeacher()) {
            return $this->scopeTeacherNotices($query, $user);
        }

        if ($user && BranchScope::for($user)->isRestricted()) {
            $ids = BranchScope::for($user)->branchIds();
            $query->where(function ($q) use ($ids) {
                $q->whereDoesntHave('branches')
                    ->orWhereHas('branches', fn ($bq) => $bq->whereIn('branches.id', $ids));
            });
        }

        return $query;
    }

    /**
     * @return Collection<int, Notice>
     */
    public function listForUser(?User $user = null, ?int $studentId = null, bool $admin = false): Collection
    {
        $query = $admin ? $this->adminQueryForUser($user) : $this->queryForUser($user, $studentId);

        return $query
            ->with(['batches:id,name', 'branches:id,name', 'creator:id,name'])
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Notices visible to a student (audience: all / students), scoped by batch.
     *
     * @return Collection<int, Notice>
     */
    public function listForStudent(Student $student): Collection
    {
        $batchIds = $student->enrollments()
            ->where('status', 'active')
            ->pluck('batch_id')
            ->unique()
            ->filter()
            ->values();

        $branchId = $student->branch_id;

        return Notice::query()
            ->where('institute_id', $student->institute_id)
            ->where('status', 'active')
            ->whereIn('audience', [Notice::AUDIENCE_ALL, Notice::AUDIENCE_STUDENTS])
            ->where(function ($q) use ($batchIds) {
                $q->whereDoesntHave('batches');
                if ($batchIds->isNotEmpty()) {
                    $q->orWhereHas('batches', fn ($bq) => $bq->whereIn('batches.id', $batchIds));
                }
            })
            ->where(function ($q) use ($branchId) {
                $q->whereDoesntHave('branches');
                if ($branchId) {
                    $q->orWhereHas('branches', fn ($bq) => $bq->where('branches.id', $branchId));
                }
            })
            ->with(['batches:id,name', 'branches:id,name'])
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Notices targeting this batch (or all-batches notices for the institute/branch).
     *
     * @return Collection<int, Notice>
     */
    public function listForBatch(Batch $batch): Collection
    {
        $branchId = $batch->branch_id;

        return Notice::query()
            ->where('institute_id', $batch->institute_id)
            ->where('status', 'active')
            ->where(function ($q) use ($batch) {
                $q->whereDoesntHave('batches')
                    ->orWhereHas('batches', fn ($bq) => $bq->where('batches.id', $batch->id));
            })
            ->where(function ($q) use ($branchId) {
                $q->whereDoesntHave('branches');
                if ($branchId) {
                    $q->orWhereHas('branches', fn ($bq) => $bq->where('branches.id', $branchId));
                }
            })
            ->with(['batches:id,name', 'branches:id,name', 'creator:id,name'])
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->get();
    }

    public function assertUserCanAccess(User $user, Notice $notice): void
    {
        if ($user && ! $user->isPlatformUser() && (int) $notice->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($user && $user->isParent()) {
            if ($notice->relationLoaded('batches') ? $notice->batches->isNotEmpty() : $notice->batches()->exists()) {
                $batchIds = ParentBatchScope::batchIds($user);
                $noticeBatchIds = $notice->relationLoaded('batches')
                    ? $notice->batches->pluck('id')
                    : $notice->batches()->pluck('batches.id');

                abort_unless(
                    $noticeBatchIds->intersect($batchIds)->isNotEmpty(),
                    403,
                    'You cannot access this notice.'
                );
            }

            abort_unless(
                in_array($notice->audience, [Notice::AUDIENCE_ALL, Notice::AUDIENCE_PARENTS], true),
                403,
                'You cannot access this notice.'
            );

            return;
        }

        if ($user && $user->isTeacher()) {
            $this->assertTeacherCanAccessNotice($user, $notice);

            return;
        }

        if ($notice->relationLoaded('branches') ? $notice->branches->isNotEmpty() : $notice->branches()->exists()) {
            $branchIds = $notice->relationLoaded('branches')
                ? $notice->branches->pluck('id')
                : $notice->branches()->pluck('branches.id');

            foreach ($branchIds as $branchId) {
                BranchScope::for($user)->assertCanAccessBranch((int) $branchId);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Notice
    {
        return DB::transaction(function () use ($data, $image) {
            $instituteId = (int) $data['institute_id'];

            $notice = Notice::query()->create([
                'institute_id' => $instituteId,
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
                'notice_date' => $data['notice_date'],
                'audience' => $data['audience'] ?? Notice::AUDIENCE_ALL,
                'status' => $data['status'] ?? 'active',
                'published_at' => ($data['status'] ?? 'active') === 'active' ? now() : null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->syncRelations($notice, $data);

            if ($image) {
                $notice->update([
                    'image_path' => $this->storeImage($image, $instituteId),
                ]);
            }

            return $notice->fresh(['batches', 'branches', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Notice $notice, array $data, ?UploadedFile $image = null, bool $removeImage = false): Notice
    {
        return DB::transaction(function () use ($notice, $data, $image, $removeImage) {
            $status = $data['status'] ?? $notice->status;
            $publishedAt = $notice->published_at;
            if ($status === 'active' && ! $publishedAt) {
                $publishedAt = now();
            }
            if ($status !== 'active') {
                $publishedAt = null;
            }

            $notice->update([
                'institute_id' => (int) ($data['institute_id'] ?? $notice->institute_id),
                'title' => $data['title'] ?? $notice->title,
                'body' => $data['body'] ?? $notice->body,
                'notice_date' => $data['notice_date'] ?? $notice->notice_date,
                'audience' => $data['audience'] ?? $notice->audience,
                'status' => $status,
                'published_at' => $publishedAt,
                'updated_by' => auth()->id(),
            ]);

            $this->syncRelations($notice, $data);

            if ($removeImage && $notice->image_path) {
                $this->deleteImage($notice->image_path);
                $notice->update(['image_path' => null]);
            }

            if ($image) {
                if ($notice->image_path) {
                    $this->deleteImage($notice->image_path);
                }
                $notice->update([
                    'image_path' => $this->storeImage($image, (int) $notice->institute_id),
                ]);
            }

            return $notice->fresh(['batches', 'branches', 'creator']);
        });
    }

    public function toggleStatus(Notice $notice): Notice
    {
        $status = $notice->status === 'active' ? 'inactive' : 'active';

        return $this->update($notice, [
            'status' => $status,
        ]);
    }

    public function delete(Notice $notice): void
    {
        if ($notice->image_path) {
            $this->deleteImage($notice->image_path);
        }

        $notice->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncRelations(Notice $notice, array $data): void
    {
        $branchIds = collect($data['branch_ids'] ?? [])->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $batchIds = collect($data['batch_ids'] ?? [])->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        $notice->branches()->sync($branchIds);
        $notice->batches()->sync($batchIds);
    }

    protected function storeImage(UploadedFile $file, int $instituteId): string
    {
        return $file->store("notices/{$instituteId}", 'public');
    }

    protected function deleteImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  Builder<Notice>  $query
     * @return Builder<Notice>
     */
    protected function scopeTeacherNotices(Builder $query, User $user): Builder
    {
        $batchIds = OwnBatchScope::batchIds($user);
        $branchIds = OwnBatchScope::branchIds($user);

        return $query->where(function ($q) use ($batchIds, $branchIds) {
            $q->where(function ($inner) use ($batchIds) {
                $inner->whereDoesntHave('batches');
                if ($batchIds !== []) {
                    $inner->orWhereHas('batches', fn ($bq) => $bq->whereIn('batches.id', $batchIds));
                }
            })->where(function ($inner) use ($branchIds) {
                $inner->whereDoesntHave('branches');
                if ($branchIds !== []) {
                    $inner->orWhereHas('branches', fn ($bq) => $bq->whereIn('branches.id', $branchIds));
                }
            });
        });
    }

    protected function assertTeacherCanAccessNotice(User $user, Notice $notice): void
    {
        $hasBatches = $notice->relationLoaded('batches') ? $notice->batches->isNotEmpty() : $notice->batches()->exists();
        if ($hasBatches) {
            $own = collect(OwnBatchScope::batchIds($user));
            $noticeBatchIds = $notice->relationLoaded('batches')
                ? $notice->batches->pluck('id')
                : $notice->batches()->pluck('batches.id');

            abort_unless(
                $noticeBatchIds->intersect($own)->isNotEmpty(),
                404,
                'Notice not found.'
            );
        }

        $hasBranches = $notice->relationLoaded('branches') ? $notice->branches->isNotEmpty() : $notice->branches()->exists();
        if ($hasBranches) {
            $own = collect(OwnBatchScope::branchIds($user));
            $noticeBranchIds = $notice->relationLoaded('branches')
                ? $notice->branches->pluck('id')
                : $notice->branches()->pluck('branches.id');

            abort_unless(
                $noticeBranchIds->intersect($own)->isNotEmpty(),
                404,
                'Notice not found.'
            );
        }
    }
}
