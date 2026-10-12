<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentBatch;
use App\Models\User;
use App\Support\BranchScope;
use App\Support\ParentBatchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExamService
{
    /**
     * @return Builder<Exam>
     */
    public function queryForUser(?User $user = null, ?int $studentId = null): Builder
    {
        $user = $user ?? auth()->user();
        $query = Exam::query();

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
     * @return Collection<int, Exam>
     */
    public function listForUser(?User $user = null, ?int $studentId = null): Collection
    {
        return $this->queryForUser($user, $studentId)
            ->with(['batch:id,name', 'subject:id,name', 'branch:id,name', 'creator:id,name', 'announcement:id,exam_id,published_at'])
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, Exam>
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

        return Exam::query()
            ->where('institute_id', $student->institute_id)
            ->whereIn('batch_id', $batchIds)
            ->with(['batch:id,name', 'subject:id,name'])
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, Exam>
     */
    public function listForBatch(Batch $batch): Collection
    {
        return Exam::query()
            ->where('batch_id', $batch->id)
            ->with(['subject:id,name', 'announcement:id,exam_id,published_at'])
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->get();
    }

    public function assertUserCanAccess(User $user, Exam $exam): void
    {
        if ($user && ! $user->isPlatformUser() && (int) $exam->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($user && $user->isParent()) {
            ParentBatchScope::assertCanAccessBatch($user, (int) $exam->batch_id);

            return;
        }

        if ($exam->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $exam->branch_id);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $attachment = null): Exam
    {
        return DB::transaction(function () use ($data, $attachment) {
            $batch = Batch::query()->findOrFail((int) $data['batch_id']);

            $exam = Exam::query()->create([
                'institute_id' => (int) $batch->institute_id,
                'branch_id' => $batch->branch_id ? (int) $batch->branch_id : null,
                'batch_id' => $batch->id,
                'subject_id' => $data['subject_id'] ?? null,
                'title' => $data['title'],
                'exam_date' => $data['exam_date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'duration_label' => $data['duration_label'] ?? null,
                'total_marks' => $data['total_marks'] ?? 0,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'scheduled',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            if ($attachment) {
                $exam->update([
                    'attachment_path' => $this->storeAttachment($attachment, (int) $batch->institute_id),
                ]);
            }

            return $exam->fresh(['batch', 'subject', 'branch', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Exam $exam, array $data, ?UploadedFile $attachment = null, bool $removeAttachment = false): Exam
    {
        return DB::transaction(function () use ($exam, $data, $attachment, $removeAttachment) {
            $batchId = (int) ($data['batch_id'] ?? $exam->batch_id);
            $batch = Batch::query()->findOrFail($batchId);

            $exam->update([
                'institute_id' => (int) $batch->institute_id,
                'branch_id' => $batch->branch_id ? (int) $batch->branch_id : null,
                'batch_id' => $batch->id,
                'subject_id' => $data['subject_id'] ?? null,
                'title' => $data['title'] ?? $exam->title,
                'exam_date' => $data['exam_date'] ?? $exam->exam_date,
                'start_time' => $data['start_time'] ?? $exam->start_time,
                'end_time' => $data['end_time'] ?? $exam->end_time,
                'duration_label' => $data['duration_label'] ?? $exam->duration_label,
                'total_marks' => $data['total_marks'] ?? $exam->total_marks,
                'description' => $data['description'] ?? $exam->description,
                'status' => $data['status'] ?? $exam->status,
                'updated_by' => auth()->id(),
            ]);

            if ($removeAttachment && $exam->attachment_path) {
                $this->deleteAttachment($exam->attachment_path);
                $exam->update(['attachment_path' => null]);
            }

            if ($attachment) {
                if ($exam->attachment_path) {
                    $this->deleteAttachment($exam->attachment_path);
                }
                $exam->update([
                    'attachment_path' => $this->storeAttachment($attachment, (int) $batch->institute_id),
                ]);
            }

            return $exam->fresh(['batch', 'subject', 'branch', 'creator']);
        });
    }

    public function delete(Exam $exam): void
    {
        if ($exam->attachment_path) {
            $this->deleteAttachment($exam->attachment_path);
        }

        $exam->delete();
    }

    /**
     * @return array{exam: Exam, present: Collection, absent: Collection, attendance_marked: bool}
     */
    public function resultSheet(Exam $exam, bool $useSavedGrouping = false): array
    {
        $exam->load(['batch', 'subject', 'results.student.standard']);

        $enrolledIds = StudentBatch::query()
            ->where('batch_id', $exam->batch_id)
            ->where('status', 'active')
            ->pluck('student_id');

        $students = Student::query()
            ->whereIn('id', $enrolledIds)
            ->where('status', 'active')
            ->with('standard:id,name')
            ->orderBy('name')
            ->get();

        $resultsByStudent = $exam->results->keyBy('student_id');

        $attendanceByStudent = StudentAttendance::query()
            ->where('batch_id', $exam->batch_id)
            ->whereDate('attendance_date', $exam->exam_date)
            ->get()
            ->keyBy('student_id');

        $attendanceMarked = $attendanceByStudent->isNotEmpty();

        $present = collect();
        $absent = collect();

        foreach ($students as $student) {
            $result = $resultsByStudent->get($student->id);
            $row = [
                'student' => $student,
                'result' => $result,
                'roll_no' => StudentBatch::query()
                    ->where('batch_id', $exam->batch_id)
                    ->where('student_id', $student->id)
                    ->value('roll_no'),
            ];

            if ($useSavedGrouping && $result) {
                if ($result->attendance_status === 'absent') {
                    $absent->push($row);
                } else {
                    $present->push($row);
                }

                continue;
            }

            if ($this->isExamDayPresent($attendanceByStudent->get($student->id)?->status, $attendanceMarked)) {
                $present->push($row);
            } else {
                $absent->push($row);
            }
        }

        return [
            'exam' => $exam,
            'present' => $present,
            'absent' => $absent,
            'attendance_marked' => $attendanceMarked,
        ];
    }

    protected function isExamDayPresent(?string $attendanceStatus, bool $attendanceMarked): bool
    {
        if (! $attendanceMarked) {
            return false;
        }

        return in_array($attendanceStatus, [
            StudentAttendance::STATUS_PRESENT,
            StudentAttendance::STATUS_LATE,
        ], true);
    }

    protected function storeAttachment(UploadedFile $file, int $instituteId): string
    {
        return $file->store("exams/{$instituteId}", 'public');
    }

    protected function deleteAttachment(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
