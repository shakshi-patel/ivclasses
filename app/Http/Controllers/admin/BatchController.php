<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Homework;
use App\Models\Institute;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\StudentBatch;
use App\Services\BatchService;
use App\Services\ExamService;
use App\Services\NoticeService;
use App\Services\StudentAttendanceService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BatchController extends AdminController
{
    public function __construct(
        protected BatchService $batchService,
        protected ExamService $examService,
        protected NoticeService $noticeService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('batch.view');

        $query = $this->batchQuery()
            ->with(['institute:id,name', 'branch:id,name', 'standard:id,name', 'subjects:id,name']);

        $this->applyInstituteFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $batches = $query->orderByDesc('id')->get();
        $filters = $this->listFilterVars($request, ['branches']);

        return view('admin.master.batch.batch', array_merge(compact('batches'), $filters));
    }

    public function create(): View
    {
        $this->ensureCan('batch.add');

        return view('admin.master.batch.add_batch', [
            'batch' => null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions(),
            'standards' => $this->standardOptions(),
            'subjects' => $this->subjectOptions(),
            'teachers' => $this->teacherOptions(),
            'batchSubjects' => [
                ['subject_id' => '', 'teacher_user_id' => '', 'fees_amount' => ''],
            ],
            'assignedTeacherIds' => [],
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('batch.add');

        [$validated, $subjects, $teacherIds] = $this->validateBatch($request);
        $batch = $this->batchService->create($validated, $subjects, $teacherIds);

        if ($request->expectsJson()) {
            return Ajax::success('Batch created.', $batch, 201);
        }

        return redirect()
            ->route('admin.master.batch')
            ->with('success', 'Batch created.');
    }

    public function show(Batch $batch): View
    {
        $this->ensureCan('batch.show');
        $this->assertVisible($batch);

        $batch->load([
            'institute:id,name',
            'branch:id,name,address,city,state,pincode',
            'standard:id,name',
            'subjects:id,name',
            'teachers:id,name,phone,email,avatar_path,user_type',
        ]);

        $teacherProfiles = Teacher::query()
            ->whereIn('user_id', $batch->teachers->pluck('id')->filter()->all() ?: [0])
            ->get(['user_id', 'employee_code'])
            ->keyBy('user_id');

        $teacherRows = $batch->subjects
            ->filter(fn ($subject) => (int) ($subject->pivot->teacher_user_id ?? 0) > 0)
            ->map(function ($subject) use ($batch, $teacherProfiles) {
                $teacherId = (int) $subject->pivot->teacher_user_id;
                $teacher = $batch->teachers->firstWhere('id', $teacherId);
                $profile = $teacherProfiles->get($teacherId);

                return [
                    'subject' => $subject->name,
                    'name' => $teacher?->name ?? '—',
                    'code' => $profile?->employee_code ?: '—',
                    'phone' => $teacher?->phone ?: '—',
                    'avatar' => $teacher
                        ? $teacher->avatarUrl()
                        : asset('admin-assets/img/roles/teacher.svg'),
                ];
            });

        // Teachers assigned directly to the batch (e.g. via their own "Assign Batches"
        // field) without being tied to a specific subject still belong on this list.
        $subjectLinkedTeacherIds = $batch->subjects
            ->map(fn ($subject) => (int) ($subject->pivot->teacher_user_id ?? 0))
            ->filter()
            ->all();

        $teacherRows = $teacherRows->concat(
            $batch->teachers
                ->reject(fn ($teacher) => in_array((int) $teacher->id, $subjectLinkedTeacherIds, true))
                ->map(function ($teacher) use ($teacherProfiles) {
                    $profile = $teacherProfiles->get($teacher->id);

                    return [
                        'subject' => 'General',
                        'name' => $teacher->name,
                        'code' => $profile?->employee_code ?: '—',
                        'phone' => $teacher->phone ?: '—',
                        'avatar' => $teacher->avatarUrl(),
                    ];
                })
        )->values();

        $attendanceDate = request('att_date', now()->toDateString());
        $attendanceService = app(StudentAttendanceService::class);
        $attendanceRoster = $attendanceService->batchRoster($batch, $attendanceDate);
        $attendanceMeta = $attendanceService->batchAttendanceMeta($batch, $attendanceDate);
        $attendanceReport = $attendanceService->batchMonthReport(
            $batch,
            (int) date('Y', strtotime($attendanceDate)),
            (int) date('n', strtotime($attendanceDate))
        );

        $batchStudents = StudentBatch::query()
            ->with(['student:id,name,admission_no,parent_id', 'student.parent:id,father_name,phone'])
            ->where('batch_id', $batch->id)
            ->where('status', 'active')
            ->orderBy('roll_no')
            ->orderBy('id')
            ->get();

        $batchHomeworks = Homework::query()
            ->with(['subject:id,name', 'creator:id,name'])
            ->where('batch_id', $batch->id)
            ->orderByDesc('due_date')
            ->limit(20)
            ->get();

        $batchExams = $this->examService->listForBatch($batch);
        $batchNotices = $this->noticeService->listForBatch($batch);

        return view('admin.master.batch.view_batch', compact(
            'batch',
            'teacherRows',
            'attendanceDate',
            'attendanceRoster',
            'attendanceMeta',
            'attendanceReport',
            'batchStudents',
            'batchHomeworks',
            'batchExams',
            'batchNotices'
        ));
    }

    public function edit(Batch $batch): View
    {
        $this->ensureCan('batch.edit');
        $this->assertVisible($batch);

        $batch->load(['subjects', 'teachers']);

        $batchSubjects = $batch->subjects->map(fn ($subject) => [
            'subject_id' => $subject->id,
            'teacher_user_id' => $subject->pivot->teacher_user_id,
            'fees_amount' => $subject->pivot->fees_amount,
        ])->values()->all();

        if (empty($batchSubjects)) {
            $batchSubjects = [['subject_id' => '', 'teacher_user_id' => '', 'fees_amount' => '']];
        }

        return view('admin.master.batch.add_batch', [
            'batch' => $batch,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($batch->institute_id),
            'standards' => $this->standardOptions($batch->institute_id),
            'subjects' => $this->subjectOptions($batch->institute_id),
            'teachers' => $this->teacherOptions($batch->institute_id),
            'batchSubjects' => $batchSubjects,
            'assignedTeacherIds' => $batch->teachers->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ]);
    }

    public function update(Request $request, Batch $batch): JsonResponse|RedirectResponse
    {
        $this->ensureCan('batch.edit');
        $this->assertVisible($batch);

        [$validated, $subjects, $teacherIds] = $this->validateBatch($request, $batch);
        $batch = $this->batchService->update($batch, $validated, $subjects, $teacherIds);

        if ($request->expectsJson()) {
            return Ajax::success('Batch updated.', $batch);
        }

        return redirect()
            ->route('admin.master.batch')
            ->with('success', 'Batch updated.');
    }

    public function destroy(Request $request, Batch $batch): JsonResponse|RedirectResponse
    {
        $this->ensureCan('batch.delete');
        $this->assertVisible($batch);

        $batch->delete();

        return $this->respondDestroyed($request, 'Batch deleted.', 'admin.master.batch');
    }

    public function toggleStatus(Batch $batch): JsonResponse
    {
        $this->ensureCan('batch.status');
        $this->assertVisible($batch);

        $batch->update([
            'status' => $batch->status === 'active' ? 'inactive' : 'active',
        ]);

        return Ajax::success(
            $batch->status === 'active' ? 'Batch activated.' : 'Batch deactivated.',
            ['id' => $batch->id, 'status' => $batch->status]
        );
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>, 2: array<int, int>}
     */
    protected function validateBatch(Request $request, ?Batch $batch = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }
        $branchIds = BranchScope::for()->allowedBranchIdsForInstitute($instituteId);
        if ($batch?->branch_id && ! in_array((int) $batch->branch_id, $branchIds, true)) {
            $branchIds[] = (int) $batch->branch_id;
        }

        $standardIds = Standard::query()
            ->when($instituteId, fn ($q) => $q->where('institute_id', $instituteId))
            ->where(function ($q) use ($batch) {
                $q->where('status', 'active');
                if ($batch?->standard_id) {
                    $q->orWhere('id', $batch->standard_id);
                }
            })
            ->pluck('id')
            ->all();

        $subjectIds = Subject::query()
            ->when($instituteId, fn ($q) => $q->where('institute_id', $instituteId))
            ->where('status', 'active')
            ->pluck('id')
            ->all();
        if ($batch) {
            $subjectIds = array_values(array_unique(array_merge(
                $subjectIds,
                $batch->subjects()->pluck('subjects.id')->all()
            )));
        }
        $teacherUserIds = BranchScope::for()
            ->applyToBranchMembers(Teacher::query())
            ->when($instituteId, fn ($q) => $q->where('institute_id', $instituteId))
            ->where('status', 'active')
            ->pluck('user_id')
            ->all();

        $branchId = (int) $request->input('branch_id');
        $nameUnique = Rule::unique('batches', 'name')
            ->where(fn ($q) => $q->where('branch_id', $branchId)->whereNull('deleted_at'));

        if ($batch) {
            $nameUnique->ignore($batch->id);
        }

        $validated = $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_id' => ['required', 'integer', Rule::in($branchIds)],
            'standard_id' => ['required', 'integer', Rule::in($standardIds)],
            'name' => ['required', 'string', 'max:150', $nameUnique],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive'],
            'subjects' => ['nullable', 'array'],
            'subjects.*.subject_id' => ['nullable', 'integer', Rule::in($subjectIds)],
            'subjects.*.teacher_user_id' => ['nullable', 'integer', Rule::in($teacherUserIds)],
            'subjects.*.fees_amount' => ['nullable', 'numeric', 'min:0'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', Rule::in($teacherUserIds)],
        ]);

        $subjects = collect($validated['subjects'] ?? [])
            ->filter(fn ($row) => ! empty($row['subject_id']))
            ->values()
            ->all();

        $teacherIds = collect($validated['teacher_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        unset($validated['subjects'], $validated['teacher_ids']);

        return [$validated, $subjects, $teacherIds];
    }

    protected function instituteOptions()
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return Institute::query()->where('id', $user->institute_id)->get(['id', 'name']);
        }

        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function branchOptions(?int $instituteId = null)
    {
        $user = auth()->user();
        $instituteId ??= $user && ! $user->isPlatformUser() ? $user->institute_id : null;

        if (! $instituteId) {
            return collect();
        }
        return BranchScope::for()->branchesForInstitute($instituteId);
    }

    protected function standardOptions(?int $instituteId = null)
    {
        $user = auth()->user();
        $instituteId ??= $user && ! $user->isPlatformUser() ? $user->institute_id : null;

        if (! $instituteId) {
            return collect();
        }

        return Standard::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function subjectOptions(?int $instituteId = null)
    {
        $user = auth()->user();
        $instituteId ??= $user && ! $user->isPlatformUser() ? $user->institute_id : null;

        if (! $instituteId) {
            return collect();
        }

        return Subject::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    protected function teacherOptions(?int $instituteId = null)
    {
        $user = auth()->user();
        $instituteId ??= $user && ! $user->isPlatformUser() ? $user->institute_id : null;

        if (! $instituteId) {
            return collect();
        }
        return BranchScope::for()
            ->applyToBranchMembers(Teacher::query())
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->with('user:id,name,email')
            ->get()
            ->map(fn (Teacher $teacher) => $teacher->user)
            ->filter()
            ->values();
    }

    protected function assertVisible(Batch $batch): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $batch->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($user && $user->isTeacher()) {
            $assigned = $batch->teachers()->where('users.id', $user->id)->exists();
            if (! $assigned) {
                abort(404);
            }
        }

        BranchScope::for($user)->assertCanAccessBranch($batch->branch_id);
    }

    protected function batchQuery()
    {
        $user = auth()->user();
        $query = Batch::query();

        if ($user && $user->isTeacher()) {
            $query->whereHas('teachers', fn ($q) => $q->where('users.id', $user->id));
        }

        return BranchScope::for($user)->applyToBatches($query);
    }

    protected function ensureCan(string $permission): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can($permission))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
