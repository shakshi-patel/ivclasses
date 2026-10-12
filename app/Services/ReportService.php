<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentBatch;
use App\Models\Todo;
use App\Models\User;
use App\Support\BranchScope;
use App\Support\Tenancy;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class ReportService
{
    public function __construct(
        protected StudentAttendanceService $attendanceService
    ) {}

    /**
     * @return Collection<int, Batch>
     */
    public function batchOptions(?User $user = null): Collection
    {
        $user ??= auth()->user();
        $q = BranchScope::for($user)->applyToBatches(Batch::query()->where('status', 'active'));

        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            $q->where('institute_id', $user->institute_id);
        }

        return $q->with(['branch:id,name', 'standard:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id', 'standard_id', 'institute_id']);
    }

    /**
     * @return Collection<int, Student>
     */
    public function studentOptions(?User $user = null): Collection
    {
        $user ??= auth()->user();
        $q = Student::query()->where('status', 'active');

        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            $q->where('institute_id', $user->institute_id);
        }

        $scope = BranchScope::for($user);
        if ($scope->isRestricted()) {
            $ids = $scope->branchIds();
            $q->whereIn('branch_id', $ids ?: [0]);
        }

        return $q->with(['branch:id,name', 'standard:id,name'])
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'admission_no', 'branch_id', 'standard_id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function attendanceBatchReport(Batch $batch, int $year, int $month): ?array
    {
        return $this->attendanceService->batchMonthReport($batch, $year, $month);
    }

    /**
     * @param  array{from?:string,to?:string,status?:string,assignee_id?:int}  $filters
     * @return Collection<int, Todo>
     */
    public function todoReport(array $filters, ?User $user = null): Collection
    {
        $user ??= auth()->user();
        $q = Todo::query()
            ->with(['assignees:id,name', 'creator:id,name'])
            ->orderByDesc('due_date')
            ->orderByDesc('id');

        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            $q->where('institute_id', $user->institute_id);
        }

        if (! empty($filters['from'])) {
            $q->whereDate('due_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->whereDate('due_date', '<=', $filters['to']);
        }
        if (! empty($filters['status']) && array_key_exists($filters['status'], Todo::STATUSES)) {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['assignee_id'])) {
            $assigneeId = (int) $filters['assignee_id'];
            $q->where(function ($inner) use ($assigneeId) {
                $inner->where('assigned_to_user_id', $assigneeId)
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $assigneeId));
            });
        }

        return $q->limit(500)->get();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function batchDetailReport(Batch $batch): ?array
    {
        $batch->load([
            'institute:id,name',
            'branch:id,name,city',
            'standard:id,name',
            'subjects:id,name',
            'teachers:id,name,phone,email',
        ]);

        $students = StudentBatch::query()
            ->with(['student:id,name,admission_no,status,parent_id', 'student.parent:id,father_name,phone'])
            ->where('batch_id', $batch->id)
            ->where('status', 'active')
            ->orderBy('roll_no')
            ->get();

        $teacherRows = $batch->subjects
            ->filter(fn ($s) => (int) ($s->pivot->teacher_user_id ?? 0) > 0)
            ->map(function ($subject) use ($batch) {
                $tid = (int) $subject->pivot->teacher_user_id;
                $teacher = $batch->teachers->firstWhere('id', $tid);

                return [
                    'subject' => $subject->name,
                    'teacher' => $teacher?->name ?? '—',
                    'phone' => $teacher?->phone ?? '—',
                    'fees' => $subject->pivot->fees_amount,
                ];
            })
            ->values();

        $year = (int) now()->year;
        $month = (int) now()->month;
        $att = $this->attendanceService->batchMonthReport($batch, $year, $month);

        $counts = ['P' => 0, 'A' => 0, 'L' => 0, 'H' => 0];
        foreach ($att['students'] ?? [] as $row) {
            foreach ($row['days'] ?? [] as $cell) {
                if (! is_array($cell) || empty($cell['label'])) {
                    continue;
                }
                $label = (string) $cell['label'];
                if (isset($counts[$label])) {
                    $counts[$label]++;
                }
            }
        }

        return [
            'batch' => $batch,
            'students' => $students,
            'teacher_rows' => $teacherRows,
            'attendance_counts' => $counts,
            'attendance_month' => sprintf('%04d-%02d', $year, $month),
            'attendance_report' => $att,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function studentDetailReport(Student $student): ?array
    {
        $student->load([
            'institute:id,name',
            'branch:id,name',
            'standard:id,name',
            'parent:id,father_name,mother_name,phone',
        ]);

        $enrollments = StudentBatch::query()
            ->with(['batch:id,name,start_time,end_time,status', 'batch.branch:id,name'])
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->get();

        $attendanceStats = StudentAttendance::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'student' => $student,
            'enrollments' => $enrollments,
            'attendance_stats' => $attendanceStats,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rolesReport(?User $user = null): Collection
    {
        $user ??= auth()->user();

        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->institute_id);
        }

        $roles = Tenancy::rolesQuery()
            ->withCount('permissions')
            ->with('parentRole:id,name')
            ->orderBy('name')
            ->get();

        $ownIds = $user ? $user->roles->pluck('id')->map(fn ($id) => (int) $id)->all() : [];

        return $roles
            ->reject(fn (Role $role) => in_array((int) $role->id, $ownIds, true))
            ->map(function (Role $role) {
                $usersCount = $role->users()->count();

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'parent' => $role->parentRole?->name ?? '—',
                    'status' => $role->status,
                    'permissions_count' => (int) $role->permissions_count,
                    'users_count' => $usersCount,
                    'created_at' => $role->created_at?->format('d M Y'),
                ];
            })
            ->values();
    }
}
