<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\BatchResource;
use App\Http\Resources\Api\V1\ExamResource;
use App\Http\Resources\Api\V1\HomeworkResource;
use App\Http\Resources\Api\V1\NoticeResource;
use App\Models\Batch;
use App\Models\Homework;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\BatchService;
use App\Services\ExamService;
use App\Services\NoticeService;
use App\Services\StudentAttendanceService;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Support\OwnBatchScope;
use App\Support\ParentBatchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BatchController extends ApiController
{
    public function __construct(
        protected BatchService $batchService,
        protected StudentAttendanceService $attendanceService,
        protected ExamService $examService,
        protected NoticeService $noticeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('batch.view');

        $query = $this->batchQuery()
            ->with(['branch:id,name', 'standard:id,name', 'subjects:id,name'])
            ->withCount('enrollments');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('standard_id')) {
            $query->where('standard_id', (int) $request->input('standard_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginated(
            'OK',
            $query->orderByDesc('id')->paginate($this->perPage())
                ->through(fn (Batch $batch) => (new BatchResource($batch))->resolve($request))
        );
    }

    public function show(Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['batch.view', 'batch.show']);
        $this->assertVisible($batch);

        $batch->load([
            'branch:id,name',
            'standard:id,name',
            'subjects:id,name',
            'teachers:id,name',
        ])->loadCount('enrollments');

        return Ajax::success('OK', (new BatchResource($batch))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('batch.add');

        [$validated, $subjects, $teacherIds] = $this->validateBatch($request);
        $batch = $this->batchService->create($validated, $subjects, $teacherIds);

        return Ajax::success('Batch created.', (new BatchResource($batch))->resolve(), 201);
    }

    public function update(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCan('batch.edit');
        $this->assertVisible($batch);

        [$validated, $subjects, $teacherIds] = $this->validateBatch($request, $batch);
        $batch = $this->batchService->update($batch, $validated, $subjects, $teacherIds);

        return Ajax::success('Batch updated.', (new BatchResource($batch))->resolve());
    }

    public function status(Batch $batch): JsonResponse
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

    public function students(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['batch.tab_students', 'batch.show', 'batch.view']);
        $this->assertVisible($batch);

        $enrollments = $batch->enrollments()
            ->with(['student:id,name,admission_no,status,branch_id,standard_id'])
            ->orderBy('roll_no')
            ->paginate($this->perPage());

        $enrollments->getCollection()->transform(fn ($row) => [
            'id' => $row->id,
            'roll_no' => $row->roll_no,
            'status' => $row->status,
            'student' => $row->student ? [
                'id' => $row->student->id,
                'name' => $row->student->name,
                'admission_no' => $row->student->admission_no,
                'status' => $row->student->status,
            ] : null,
        ]);

        return $this->paginated('OK', $enrollments);
    }

    public function attendance(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['batch.tab_attendance', 'attendance.view']);
        $this->assertVisible($batch);

        if (! $request->filled('date')) {
            $request->merge(['date' => now()->toDateString()]);
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        return Ajax::success('Attendance loaded.', [
            'date' => $validated['date'],
            'students' => $this->attendanceService->batchRoster($batch, $validated['date'])->all(),
            'meta' => $this->attendanceService->batchAttendanceMeta($batch, $validated['date']),
        ]);
    }

    public function attendanceReport(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['batch.tab_attendance_report', 'attendance.view']);
        $this->assertVisible($batch);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        return Ajax::success('Report loaded.', $this->attendanceService->batchMonthReport(
            $batch,
            (int) $validated['year'],
            (int) $validated['month']
        ));
    }

    public function exams(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['batch.tab_exam', 'exam.view']);
        $this->assertVisible($batch);

        $exams = $this->examService->listForBatch($batch);

        return Ajax::success('OK', [
            'items' => $exams->map(fn ($exam) => (new ExamResource($exam))->resolve($request))->values()->all(),
        ]);
    }

    public function homework(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['batch.tab_homework', 'homework.view']);
        $this->assertVisible($batch);

        $rows = Homework::query()
            ->with(['subject:id,name', 'creator:id,name', 'batch:id,name'])
            ->where('batch_id', $batch->id)
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->paginate($this->perPage());

        $rows->getCollection()->transform(
            fn (Homework $homework) => (new HomeworkResource($homework))->resolve($request)
        );

        return $this->paginated('OK', $rows);
    }

    public function notices(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureCanAny(['batch.tab_notice', 'notice.view']);
        $this->assertVisible($batch);

        $notices = $this->noticeService->listForBatch($batch);

        return Ajax::success('OK', [
            'items' => $notices->map(fn ($notice) => (new NoticeResource($notice))->resolve($request))->values()->all(),
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>, 2: array<int, int>}
     */
    protected function validateBatch(Request $request, ?Batch $batch = null): array
    {
        $user = $this->actor();
        $instituteId = (int) $user->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        $branchIds = BranchScope::for($user)->allowedBranchIdsForInstitute($instituteId);
        if ($batch?->branch_id && ! in_array((int) $batch->branch_id, $branchIds, true)) {
            $branchIds[] = (int) $batch->branch_id;
        }

        $standardIds = Standard::query()
            ->where('institute_id', $instituteId)
            ->where(function ($q) use ($batch) {
                $q->where('status', 'active');
                if ($batch?->standard_id) {
                    $q->orWhere('id', $batch->standard_id);
                }
            })
            ->pluck('id')
            ->all();

        $subjectIds = Subject::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->pluck('id')
            ->all();
        if ($batch) {
            $subjectIds = array_values(array_unique(array_merge(
                $subjectIds,
                $batch->subjects()->pluck('subjects.id')->all()
            )));
        }

        $teacherUserIds = BranchScope::for($user)
            ->applyToBranchMembers(Teacher::query())
            ->where('institute_id', $instituteId)
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
            'branch_id' => ['required', 'integer', Rule::in($branchIds ?: [0])],
            'standard_id' => ['required', 'integer', Rule::in($standardIds ?: [0])],
            'name' => ['required', 'string', 'max:150', $nameUnique],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive'],
            'subjects' => ['nullable', 'array'],
            'subjects.*.subject_id' => ['nullable', 'integer', Rule::in($subjectIds ?: [0])],
            'subjects.*.teacher_user_id' => ['nullable', 'integer', Rule::in($teacherUserIds ?: [0])],
            'subjects.*.fees_amount' => ['nullable', 'numeric', 'min:0'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', Rule::in($teacherUserIds ?: [0])],
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

    protected function assertVisible(Batch $batch): void
    {
        $user = $this->actor();
        if ((int) $batch->institute_id !== (int) $user->institute_id) {
            abort(404, 'Batch not found.');
        }

        if ($user->isTeacher()) {
            OwnBatchScope::assertCanAccessBatch($user, (int) $batch->id);

            return;
        }

        if ($user->isParent()) {
            ParentBatchScope::assertCanAccessBatch($user, (int) $batch->id);

            return;
        }

        BranchScope::for($user)->assertCanAccessBranch($batch->branch_id);
    }

    protected function batchQuery()
    {
        $user = $this->actor();
        $query = Batch::query()->where('institute_id', $user->institute_id);

        if ($user->isTeacher()) {
            $ids = OwnBatchScope::batchIds($user);

            return $ids === [] ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
        }

        if ($user->isParent()) {
            $ids = ParentBatchScope::batchIds($user, $this->parentStudentId())->map(fn ($id) => (int) $id)->all();

            return $ids === [] ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
        }

        return BranchScope::for($user)->applyToBatches($query);
    }
}
