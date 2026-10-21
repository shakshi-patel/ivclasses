<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ResultAnnouncement;
use App\Models\Student;
use App\Models\User;
use App\Support\BranchScope;
use App\Support\OwnBatchScope;
use App\Support\ParentBatchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResultAnnouncementService
{
    public function __construct(
        protected ExamService $examService
    ) {}

    /**
     * @return Builder<ResultAnnouncement>
     */
    public function queryForUser(?User $user = null, ?int $studentId = null): Builder
    {
        $user = $user ?? auth()->user();
        $query = ResultAnnouncement::query()->whereNotNull('published_at');

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if ($user && $user->isParent()) {
            $batchIds = ParentBatchScope::batchIds($user, $studentId);
            if ($batchIds->isEmpty()) {
                return $query->whereRaw('0 = 1');
            }

            return $query->whereHas('exam', fn ($q) => $q->whereIn('batch_id', $batchIds));
        }

        if ($user && $user->isTeacher()) {
            $ids = OwnBatchScope::batchIds($user);

            return $ids === []
                ? $query->whereRaw('0 = 1')
                : $query->whereHas('exam', fn ($q) => $q->whereIn('batch_id', $ids));
        }

        if ($user && BranchScope::for($user)->isRestricted()) {
            $ids = BranchScope::for($user)->branchIds();
            $query->whereHas('exam', fn ($q) => $q->whereIn('branch_id', $ids));
        }

        return $query;
    }

    /**
     * @return Builder<ResultAnnouncement>
     */
    public function adminQueryForUser(?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        $query = ResultAnnouncement::query();

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if ($user && $user->isTeacher()) {
            $ids = OwnBatchScope::batchIds($user);

            return $ids === []
                ? $query->whereRaw('0 = 1')
                : $query->whereHas('exam', fn ($q) => $q->whereIn('batch_id', $ids));
        }

        if ($user && BranchScope::for($user)->isRestricted()) {
            $ids = BranchScope::for($user)->branchIds();
            $query->whereHas('exam', fn ($q) => $q->whereIn('branch_id', $ids));
        }

        return $query;
    }

    /**
     * @return Collection<int, ResultAnnouncement>
     */
    public function listForUser(?User $user = null, ?int $studentId = null, bool $admin = false): Collection
    {
        $query = $admin ? $this->adminQueryForUser($user) : $this->queryForUser($user, $studentId);

        return $query
            ->with([
                'exam.batch:id,name',
                'exam.subject:id,name',
                'exam.branch:id,name',
                'creator:id,name',
            ])
            ->orderByDesc('result_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Published result announcements for a student's batches, with their marks attached.
     *
     * @return Collection<int, ResultAnnouncement>
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

        $announcements = ResultAnnouncement::query()
            ->where('institute_id', $student->institute_id)
            ->whereNotNull('published_at')
            ->whereHas('exam', fn ($q) => $q->whereIn('batch_id', $batchIds))
            ->with(['exam.subject:id,name', 'exam.batch:id,name'])
            ->orderByDesc('result_date')
            ->orderByDesc('id')
            ->get();

        $examIds = $announcements->pluck('exam_id')->filter()->unique()->values();
        $resultsByExam = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereIn('exam_id', $examIds)
            ->get()
            ->keyBy('exam_id');

        foreach ($announcements as $announcement) {
            $announcement->setRelation('studentResult', $resultsByExam->get($announcement->exam_id));
        }

        return $announcements;
    }

    public function assertUserCanAccess(User $user, ResultAnnouncement $announcement): void
    {
        if ($user && ! $user->isPlatformUser() && (int) $announcement->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        $announcement->loadMissing('exam');
        if ($user && $user->isParent() && $announcement->exam) {
            ParentBatchScope::assertCanAccessBatch($user, (int) $announcement->exam->batch_id);

            abort_unless($announcement->published_at !== null, 403, 'This result is not published yet.');

            return;
        }

        if ($user && $user->isTeacher() && $announcement->exam) {
            OwnBatchScope::assertCanAccessBatch($user, (int) $announcement->exam->batch_id);

            return;
        }

        if ($announcement->exam?->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $announcement->exam->branch_id);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function saveForExam(Exam $exam, array $rows, ?string $resultDate = null, bool $publish = true): ResultAnnouncement
    {
        return DB::transaction(function () use ($exam, $rows, $resultDate, $publish) {
            foreach ($rows as $row) {
                ExamResult::query()->updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'student_id' => (int) $row['student_id'],
                    ],
                    [
                        'marks_obtained' => $row['marks_obtained'] ?? null,
                        'grade' => $row['grade'] ?? null,
                        'remark' => $row['remark'] ?? null,
                        'is_attempted' => (bool) ($row['is_attempted'] ?? true),
                        'attendance_status' => $row['attendance_status'] ?? 'present',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );
            }

            $announcement = ResultAnnouncement::query()->updateOrCreate(
                ['exam_id' => $exam->id],
                [
                    'institute_id' => $exam->institute_id,
                    'title' => $exam->title,
                    'body' => $exam->description,
                    'result_date' => $resultDate ?: now()->toDateString(),
                    'published_at' => $publish ? now() : null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            if ($publish) {
                $exam->update(['status' => 'completed', 'updated_by' => auth()->id()]);
            }

            return $announcement->fresh(['exam.batch', 'exam.subject', 'exam.branch']);
        });
    }

    public function delete(ResultAnnouncement $announcement): void
    {
        ExamResult::query()->where('exam_id', $announcement->exam_id)->delete();
        $announcement->delete();
    }
}
