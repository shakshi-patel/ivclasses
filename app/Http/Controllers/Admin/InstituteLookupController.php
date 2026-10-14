<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Standard;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\StudentService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cascading dropdown lookups scoped to an institute.
 * Platform admin may pass any institute_id; institute users are locked to their own.
 */
class InstituteLookupController extends AdminController
{
    public function branches(Request $request): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);

        $items = BranchScope::for()
            ->applyToBranches(Branch::query())
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Branch $b) => ['id' => $b->id, 'text' => $b->name]);

        return Ajax::success('OK', $items);
    }

    public function roles(Request $request): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);

        app(PermissionRegistrar::class)->setPermissionsTeamId($instituteId);

        $exclude = ['developer', 'institute_admin', 'teacher'];
        if ($request->boolean('include_teacher')) {
            $exclude = ['developer', 'institute_admin'];
        }

        $items = Role::query()
            ->where('team_id', $instituteId)
            ->where('guard_name', 'web')
            ->where('status', 'active')
            ->whereNotIn('name', $exclude)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $r) => [
                'id' => $r->id,
                'text' => str_replace('_', ' ', ucwords($r->name, '_')),
            ]);

        return Ajax::success('OK', $items);
    }

    public function managers(Request $request): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);

        $items = User::query()
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'text' => $u->name.($u->email ? ' ('.$u->email.')' : ''),
            ]);

        return Ajax::success('OK', $items);
    }

    public function standards(Request $request): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);

        $items = Standard::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Standard $s) => ['id' => $s->id, 'text' => $s->name]);

        return Ajax::success('OK', $items);
    }

    public function subjects(Request $request): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);

        $items = Subject::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Subject $s) => [
                'id' => $s->id,
                'text' => $s->code ? $s->name.' ('.$s->code.')' : $s->name,
            ]);

        return Ajax::success('OK', $items);
    }

    public function teachers(Request $request): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);

        $items = BranchScope::for()
            ->applyToBranchMembers(Teacher::query())
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->with('user:id,name,email')
            ->get()
            ->map(function (Teacher $teacher) {
                if (! $teacher->user) {
                    return null;
                }

                return [
                    'id' => $teacher->user_id,
                    'text' => $teacher->user->name.($teacher->user->email ? ' ('.$teacher->user->email.')' : ''),
                ];
            })
            ->filter()
            ->values();

        return Ajax::success('OK', $items);
    }

    public function batches(Request $request): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);
        $branchId = (int) $request->query('branch_id', 0);
        $branchIds = collect($request->input('branch_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($branchId > 0 && empty($branchIds)) {
            $branchIds = [$branchId];
        }

        $query = Batch::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->with(['subjects:id,name']);

        if (! empty($branchIds)) {
            $query->whereIn('branch_id', $branchIds);
        }

        $scope = BranchScope::for();
        if ($scope->isRestricted()) {
            $query->whereIn('branch_id', $scope->branchIds());
        }

        $items = $query->orderBy('name')->get()
            ->unique('id')
            ->values()
            ->map(fn (Batch $batch) => [
                'id' => $batch->id,
                'text' => $batch->name,
                'branch_id' => $batch->branch_id,
                'subjects' => $batch->subjects->map(fn ($s) => ['id' => $s->id, 'text' => $s->name])->values(),
            ]);

        return Ajax::success('OK', $items);
    }

    public function nextRollNo(Request $request, StudentService $studentService): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);
        $batchId = (int) $request->query('batch_id', 0);
        abort_unless($batchId > 0, 422, 'batch_id is required.');

        $batch = Batch::query()
            ->where('institute_id', $instituteId)
            ->whereKey($batchId)
            ->firstOrFail();

        $scope = BranchScope::for();
        if ($scope->isRestricted()) {
            abort_unless(in_array((int) $batch->branch_id, $scope->branchIds(), true), 403);
        }

        $excludeStudentId = (int) $request->query('exclude_student_id', 0) ?: null;

        return Ajax::success('OK', [
            'batch_id' => $batch->id,
            'next_roll' => $studentService->nextRollNoForBatch($batch->id, $excludeStudentId),
        ]);
    }

    public function exams(Request $request): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);
        $batchId = (int) $request->query('batch_id', 0);

        $query = Exam::query()
            ->where('institute_id', $instituteId)
            ->orderByDesc('exam_date')
            ->orderByDesc('id');

        if ($batchId > 0) {
            $query->where('batch_id', $batchId);
        }

        $items = $query->get(['id', 'title', 'exam_date'])->map(fn (Exam $exam) => [
            'id' => $exam->id,
            'text' => $exam->title.' | '.$exam->exam_date?->format('Y-m-d'),
        ]);

        return Ajax::success('OK', $items);
    }

    public function students(Request $request): JsonResponse
    {
        $instituteId = $this->resolveInstituteId($request);
        $branchId = (int) $request->query('branch_id', 0);
        $batchId = (int) $request->query('batch_id', 0);

        $query = Student::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('name');

        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        if ($batchId > 0) {
            $query->whereHas('enrollments', function ($q) use ($batchId) {
                $q->where('batch_id', $batchId)->where('status', 'active');
            });
        }

        $scope = BranchScope::for();
        if ($scope->isRestricted()) {
            $ids = $scope->branchIds();
            $query->whereIn('branch_id', $ids);
        }

        $items = $query->get(['id', 'name', 'admission_no'])->map(fn (Student $student) => [
            'id' => $student->id,
            'text' => $student->name.' ('.$student->admission_no.')',
        ]);

        return Ajax::success('OK', $items);
    }

    protected function resolveInstituteId(Request $request): int
    {
        $user = $request->user();
        abort_unless($user, 401);

        if (! $user->isPlatformUser()) {
            abort_unless($user->institute_id, 403);

            return (int) $user->institute_id;
        }

        $instituteId = (int) $request->query('institute_id', 0);
        abort_if($instituteId < 1, 422, 'institute_id is required.');

        return $instituteId;
    }
}
