<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\HomeworkResource;
use App\Http\Resources\Api\V1\TeacherResource;
use App\Models\Batch;
use App\Models\LoginLog;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\HomeworkService;
use App\Services\StaffAttendanceService;
use App\Services\TeacherService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends ApiController
{
    public function __construct(
        protected StaffAttendanceService $attendanceService,
        protected HomeworkService $homeworkService,
        protected TeacherService $teacherService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCanAny(['teacher.view', 'users.view_teacher']);

        $query = BranchScope::for($this->actor())
            ->applyToBranchMembers(Teacher::query())
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['user:id,name,email,phone,avatar_path']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('employee_code', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"));
            });
        }

        return $this->paginated(
            'OK',
            $query->orderByDesc('id')->paginate($this->perPage())
                ->through(fn (Teacher $teacher) => (new TeacherResource($teacher))->resolve($request))
        );
    }

    public function show(Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher.show', 'users.view_teacher']);
        $this->assertVisible($teacher);

        $teacher->load(['user:id,name,email,phone,avatar_path', 'branches:id,name']);

        return Ajax::success('OK', (new TeacherResource($teacher))->resolve());
    }

    public function batches(Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher.tab_batches', 'teacher.show', 'users.view_teacher']);
        $this->assertVisible($teacher);

        $batches = $teacher->assignedBatches()
            ->with(['branch:id,name', 'standard:id,name'])
            ->get(['batches.id', 'batches.name', 'batches.branch_id', 'batches.standard_id', 'batches.status']);

        return Ajax::success('OK', [
            'items' => $batches->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'status' => $b->status,
                'is_primary' => (bool) ($b->pivot->is_primary ?? false),
                'branch' => $b->branch ? ['id' => $b->branch->id, 'name' => $b->branch->name] : null,
                'standard' => $b->standard ? ['id' => $b->standard->id, 'name' => $b->standard->name] : null,
            ])->values()->all(),
        ]);
    }

    public function loginHistory(Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher.tab_login_history', 'teacher.show', 'users.view_teacher']);
        $this->assertVisible($teacher);

        $logs = LoginLog::query()
            ->where('user_id', $teacher->user_id)
            ->orderByDesc('logged_at')
            ->orderByDesc('id')
            ->paginate($this->perPage());

        $logs->getCollection()->transform(fn (LoginLog $log) => [
            'id' => $log->id,
            'event' => $log->event,
            'portal' => $log->portal,
            'ip' => $log->ip,
            'logged_at' => $log->logged_at,
        ]);

        return $this->paginated('OK', $logs);
    }

    public function attendance(Request $request, Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher.tab_attendance', 'teacher_attendance.view', 'teacher_attendance.show', 'teacher.show', 'users.view_teacher']);
        $this->assertVisible($teacher);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $branchId = $teacher->primaryBranch()?->id;

        return Ajax::success('Attendance loaded.', $this->attendanceService->userMonthCalendar(
            (int) $teacher->user_id,
            (int) $teacher->institute_id,
            $branchId ? (int) $branchId : null,
            (int) $validated['year'],
            (int) $validated['month']
        ));
    }

    public function assignedWork(Request $request, Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher.tab_assigned_work', 'teacher.show', 'users.view_teacher']);
        $this->assertVisible($teacher);

        $rows = $this->homeworkService->paginateForTeacher($teacher, $this->perPage(), 'page');
        $rows->getCollection()->transform(
            fn ($homework) => (new HomeworkResource($homework))->resolve($request)
        );

        return $this->paginated('OK', $rows);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCanAny(['teacher.add', 'users.add_teacher']);

        $validated = $this->validateTeacher($request);
        if ($limitError = $this->teacherService->staffLimitError((int) $validated['institute_id'])) {
            return Ajax::error($limitError, null, 403);
        }

        $validated['avatar'] = $request->file('avatar');
        $teacher = $this->teacherService->create($validated);

        return Ajax::success(
            'Teacher created.',
            (new TeacherResource($teacher->load(['user:id,name,email,phone,avatar_path', 'branches:id,name'])))->resolve(),
            201
        );
    }

    public function update(Request $request, Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher.edit', 'users.edit_teacher']);
        $this->assertVisible($teacher);

        $validated = $this->validateTeacher($request, $teacher);
        $validated['avatar'] = $request->file('avatar');
        $this->teacherService->update($teacher, $validated);

        return Ajax::success(
            'Teacher updated.',
            (new TeacherResource($teacher->fresh(['user:id,name,email,phone,avatar_path', 'branches:id,name'])))->resolve()
        );
    }

    public function destroy(Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher.delete', 'users.delete_teacher']);
        $this->assertVisible($teacher);
        $this->teacherService->delete($teacher);

        return Ajax::success('Teacher deleted.');
    }

    public function status(Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher.status', 'users.status_teacher']);
        $this->assertVisible($teacher);

        $updated = $this->teacherService->toggleStatus($teacher);

        return Ajax::success(
            $updated->status === 'active' ? 'Teacher activated.' : 'Teacher deactivated.',
            (new TeacherResource($updated->load(['user:id,name,email,phone,avatar_path'])))->resolve()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateTeacher(Request $request, ?Teacher $teacher = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);
        $branchIds = BranchScope::for($this->actor())->allowedBranchIdsForInstitute($instituteId);
        $branchId = (int) $request->input('primary_branch_id');
        $allowedBatchIds = $this->allowedBatchIds($instituteId, $branchId);

        $rules = [
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($teacher?->user_id),
            ],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'specialization_ids' => ['nullable', 'array'],
            'specialization_ids.*' => [
                'integer',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q
                    ->where('institute_id', $instituteId)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'expected_login_time' => ['nullable', 'date_format:H:i'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'joining_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'primary_branch_id' => ['required', 'integer', Rule::in($branchIds ?: [0])],
            'batch_ids' => ['required', 'array', 'min:1'],
            'batch_ids.*' => ['integer', Rule::in($allowedBatchIds ?: [0])],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($branchIds ?: [0])],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];

        $rules['password'] = $teacher
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        $validated = $request->validate($rules);
        $validated['specialization'] = $this->specializationFromIds(
            $instituteId,
            array_map('intval', (array) ($validated['specialization_ids'] ?? []))
        );
        unset($validated['specialization_ids']);

        return $validated;
    }

    /**
     * @param  array<int, int>  $subjectIds
     */
    protected function specializationFromIds(int $instituteId, array $subjectIds): ?string
    {
        $subjectIds = array_values(array_unique(array_filter($subjectIds)));
        if ($subjectIds === [] || $instituteId < 1) {
            return null;
        }

        $names = Subject::query()
            ->where('institute_id', $instituteId)
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values();

        return $names->isEmpty() ? null : $names->implode(', ');
    }

    /**
     * @return array<int>
     */
    protected function allowedBatchIds(int $instituteId, int $branchId): array
    {
        if ($branchId < 1) {
            return [];
        }

        $query = Batch::query()
            ->where('institute_id', $instituteId)
            ->where('branch_id', $branchId)
            ->where('status', 'active');

        BranchScope::for($this->actor())->applyToBatches($query);

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    protected function assertVisible(Teacher $teacher): void
    {
        if ((int) $teacher->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Teacher not found.');
        }

        BranchScope::for($this->actor())->assertSharesBranchWith($teacher->user);
    }
}
