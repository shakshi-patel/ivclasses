<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Institute;
use App\Models\LoginLog;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\HomeworkService;
use App\Services\TeacherService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherController extends AdminController
{
    public function __construct(
        protected TeacherService $teacherService,
        protected HomeworkService $homeworkService
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $this->ensureCan('teacher.view');

        return redirect()->route('admin.users', ['role' => 'teacher'] + $request->query());
    }

    public function create(): View
    {
        $this->ensureCanAny(['teacher.add', 'users.add_teacher']);

        $instituteId = $this->currentInstituteId();

        return view('admin.master.teacher.add_teacher', [
            'teacher' => null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions(),
            'batches' => collect(),
            'subjects' => $this->subjectOptions($instituteId),
            'selectedBatchIds' => array_map('intval', (array) old('batch_ids', [])),
            'selectedSpecializationIds' => array_map('intval', (array) old('specialization_ids', [])),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCanAny(['teacher.add', 'users.add_teacher']);

        $validated = $this->validateTeacher($request);
        $instituteId = (int) $validated['institute_id'];

        if ($limitError = $this->teacherService->staffLimitError($instituteId)) {
            if ($request->expectsJson()) {
                return Ajax::error($limitError, null, 403);
            }

            return back()->withInput()->withErrors(['institute_id' => $limitError]);
        }

        $validated['avatar'] = $request->file('avatar');
        $teacher = $this->teacherService->create($validated);

        if ($request->expectsJson()) {
            return Ajax::success('Teacher created.', $teacher, 201);
        }

        return redirect()
            ->route('admin.users', ['role' => 'teacher'])
            ->with('success', 'Teacher created. They can log in with their email.');
    }

    public function show(Request $request, Teacher $teacher): View
    {
        $this->ensureCanAny(['teacher.show', 'users.view_teacher']);
        $this->assertVisible($teacher);

        $teacher->load([
            'user',
            'institute',
            'branches',
            'assignedBatches' => fn ($q) => $q->with(['branch:id,name', 'standard:id,name', 'subjects:id,name']),
        ]);

        $teacherHomeworks = $this->homeworkService->paginateForTeacher($teacher, 8, 'work_page');
        $teacherHomeworks->appends($request->except('work_page') + ['tab' => 'work']);

        $loginLogs = LoginLog::query()
            ->where('user_id', $teacher->user_id)
            ->orderByDesc('logged_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'login_page');
        $loginLogs->appends($request->except('login_page') + ['tab' => 'login']);

        return view('admin.master.teacher.view_teacher', compact(
            'teacher',
            'teacherHomeworks',
            'loginLogs'
        ));
    }

    public function edit(Teacher $teacher): View
    {
        $this->ensureCanAny(['teacher.edit', 'users.edit_teacher']);
        $this->assertVisible($teacher);

        $teacher->load(['user', 'branches', 'assignedBatches']);

        $branchId = (int) old('primary_branch_id', $teacher->primaryBranch()?->id);

        return view('admin.master.teacher.add_teacher', [
            'teacher' => $teacher,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($teacher->institute_id),
            'batches' => $this->batchOptions($teacher->institute_id, $branchId ?: null),
            'subjects' => $this->subjectOptions((int) $teacher->institute_id),
            'selectedBatchIds' => array_map(
                'intval',
                (array) old('batch_ids', $teacher->assignedBatches->pluck('id')->all())
            ),
            'selectedSpecializationIds' => $this->selectedSpecializationIds($teacher, (int) $teacher->institute_id),
        ]);
    }

    public function update(Request $request, Teacher $teacher): JsonResponse|RedirectResponse
    {
        $this->ensureCanAny(['teacher.edit', 'users.edit_teacher']);
        $this->assertVisible($teacher);

        $validated = $this->validateTeacher($request, $teacher);
        $validated['avatar'] = $request->file('avatar');
        $this->teacherService->update($teacher, $validated);

        if ($request->expectsJson()) {
            return Ajax::success('Teacher updated.', $teacher->fresh(['user', 'branches']));
        }

        return redirect()
            ->route('admin.users', ['role' => 'teacher'])
            ->with('success', 'Teacher updated.');
    }

    public function destroy(Request $request, Teacher $teacher): JsonResponse|RedirectResponse
    {
        $this->ensureCanAny(['teacher.delete', 'users.delete_teacher']);
        $this->assertVisible($teacher);

        $this->teacherService->delete($teacher);

        if ($request->expectsJson()) {
            return Ajax::success('Teacher deleted.');
        }

        return redirect()
            ->route('admin.users', ['role' => 'teacher'])
            ->with('success', 'Teacher deleted.');
    }

    public function toggleStatus(Teacher $teacher): JsonResponse
    {
        $this->ensureCanAny(['teacher.status', 'users.status_teacher']);
        $this->assertVisible($teacher);

        $updated = $this->teacherService->toggleStatus($teacher);

        return Ajax::success(
            $updated->status === 'active' ? 'Teacher activated.' : 'Teacher deactivated.',
            ['id' => $updated->id, 'status' => $updated->status]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateTeacher(Request $request, ?Teacher $teacher = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }
        $branchIds = BranchScope::for()->allowedBranchIdsForInstitute($instituteId);
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
            'phone' => ['nullable', 'string', 'max:15'],
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
            'primary_branch_id' => ['required', 'integer', Rule::in($branchIds)],
            'batch_ids' => ['required', 'array', 'min:1'],
            'batch_ids.*' => ['integer', Rule::in($allowedBatchIds)],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($branchIds)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];

        if ($teacher) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

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
     * @return array<int, int>
     */
    protected function selectedSpecializationIds(?Teacher $teacher, ?int $instituteId): array
    {
        if (old('specialization_ids') !== null) {
            return array_map('intval', (array) old('specialization_ids'));
        }

        if (! $teacher?->specialization || ! $instituteId) {
            return [];
        }

        $names = collect(explode(',', $teacher->specialization))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->values();

        if ($names->isEmpty()) {
            return [];
        }

        return Subject::query()
            ->where('institute_id', $instituteId)
            ->whereIn('name', $names->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function subjectOptions(?int $instituteId = null)
    {
        if (! $instituteId) {
            return collect();
        }

        return Subject::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    protected function currentInstituteId(): ?int
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return (int) $user->institute_id;
        }

        return null;
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

        BranchScope::for()->applyToBatches($query);

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    protected function batchOptions(?int $instituteId = null, ?int $branchId = null)
    {
        if (! $instituteId || ! $branchId) {
            return collect();
        }

        $query = Batch::query()
            ->where('institute_id', $instituteId)
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->orderBy('name');

        BranchScope::for()->applyToBatches($query);

        return $query->get(['id', 'name']);
    }

    protected function assertVisible(Teacher $teacher): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $teacher->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        BranchScope::for($user)->assertSharesBranchWith($teacher->user);
    }
}
