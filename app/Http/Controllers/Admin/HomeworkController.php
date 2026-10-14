<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Homework;
use App\Models\Institute;
use App\Services\HomeworkService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeworkController extends AdminController
{
    public function __construct(
        protected HomeworkService $homeworkService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCanView();

        $user = auth()->user();
        $studentId = $user && $user->isParent() ? (int) $request->query('student_id', 0) ?: null : null;
        $isParent = $user && $user->isParent();

        $query = $this->homeworkService->queryForUser($user, $studentId);

        if (! $isParent) {
            $this->applyInstituteFilter($query, $request);

            if ($request->filled('branch_id')) {
                $query->where('branch_id', (int) $request->input('branch_id'));
            }

            if ($request->filled('batch_id')) {
                $query->where('batch_id', (int) $request->input('batch_id'));
            }
        }

        $homeworks = $query
            ->with(['batch:id,name', 'subject:id,name', 'creator:id,name', 'branch:id,name'])
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->get();

        $parentStudents = $isParent
            ? $this->homeworkService->parentStudentOptions($user)
            : collect();

        $filters = $isParent ? ['isPlatform' => false] : $this->listFilterVars($request, ['branches', 'batches']);

        return view('admin.homework.homework', array_merge([
            'homeworks' => $homeworks,
            'isParentView' => $isParent,
            'parentStudents' => $parentStudents,
            'selectedStudentId' => $studentId,
            'canAdd' => $user && ($user->isPlatformUser() || $user->can('homework.add')),
            'canShow' => $user && ($user->isPlatformUser() || $user->can('homework.show')),
            'canDownload' => $user && ($user->isPlatformUser() || $user->can('homework.download')),
            'canEdit' => $user && ($user->isPlatformUser() || $user->can('homework.edit')),
            'canDelete' => $user && ($user->isPlatformUser() || $user->can('homework.delete')),
        ], $filters));
    }

    public function create(Request $request): View
    {
        $this->ensureCan('homework.add');

        $instituteId = $this->resolveInstituteId();
        $defaultBranchId = null;
        $defaultBatchId = (int) $request->query('batch_id', 0) ?: null;

        if ($defaultBatchId) {
            $batch = Batch::query()->find($defaultBatchId);
            if ($batch) {
                $instituteId = (int) $batch->institute_id;
                $defaultBranchId = $batch->branch_id ? (int) $batch->branch_id : null;
            } else {
                $defaultBatchId = null;
            }
        }

        return view('admin.homework.add_homework', [
            'homework' => null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($instituteId),
            'defaultInstituteId' => $instituteId,
            'defaultBranchId' => $defaultBranchId,
            'defaultBatchId' => $defaultBatchId,
            'defaultSubjectId' => null,
        ]);
    }

    public function edit(Homework $homework): View
    {
        $this->ensureCan('homework.edit');
        $this->assertVisible($homework);

        $homework->load(['batch:id,name,branch_id,institute_id']);

        return view('admin.homework.add_homework', [
            'homework' => $homework,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($homework->institute_id),
            'defaultInstituteId' => $homework->institute_id,
            'defaultBranchId' => $homework->branch_id,
            'defaultBatchId' => $homework->batch_id,
            'defaultSubjectId' => $homework->subject_id,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('homework.add');

        $validated = $this->validateHomework($request);
        $homework = $this->homeworkService->create($validated, $request->file('attachment'));

        if ($request->expectsJson()) {
            return Ajax::success('Homework saved.', $homework, 201);
        }

        return redirect()->route('admin.homework')->with('success', 'Homework saved.');
    }

    public function update(Request $request, Homework $homework): JsonResponse|RedirectResponse
    {
        $this->ensureCan('homework.edit');
        $this->assertVisible($homework);

        $validated = $this->validateHomework($request, $homework);
        $homework = $this->homeworkService->update(
            $homework,
            $validated,
            $request->file('attachment'),
            $request->boolean('remove_attachment')
        );

        if ($request->expectsJson()) {
            return Ajax::success('Homework updated.', $homework);
        }

        return redirect()->route('admin.homework')->with('success', 'Homework updated.');
    }

    public function destroy(Request $request, Homework $homework): JsonResponse|RedirectResponse
    {
        $this->ensureCan('homework.delete');
        $this->assertVisible($homework);

        $this->homeworkService->delete($homework);

        return $this->respondDestroyed($request, 'Homework deleted.', 'admin.homework');
    }

    public function download(Homework $homework): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        $this->ensureCan('homework.download');
        $this->assertVisible($homework);

        if (! $homework->attachment_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($homework->attachment_path)) {
            abort(404, 'File not found.');
        }

        $name = basename($homework->attachment_path);

        return \Illuminate\Support\Facades\Storage::disk('public')->download($homework->attachment_path, $name);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateHomework(Request $request, ?Homework $homework = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        $type = $request->input('type', Homework::TYPE_TEXT);
        $needsFile = in_array($type, [Homework::TYPE_FILE, Homework::TYPE_FILE_TEXT], true);
        $hasExistingFile = $homework && $homework->attachment_path;

        $rules = [
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('batches', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)),
            ],
            'subject_id' => [
                'nullable',
                'integer',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)),
            ],
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', Rule::in(array_keys(Homework::TYPES))],
            'description' => ['nullable', 'string', 'max:5000'],
            'homework_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:homework_date'],
            'attachment' => [
                $needsFile && ! $hasExistingFile && ! $request->boolean('remove_attachment') ? 'required' : 'nullable',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,jpg,jpeg,png,zip',
            ],
            'remove_attachment' => ['sometimes', 'boolean'],
        ];

        if (in_array($type, [Homework::TYPE_TEXT, Homework::TYPE_FILE_TEXT], true)) {
            $rules['description'] = ['required', 'string', 'max:5000'];
        }

        $validated = $request->validate($rules);

        if (empty($validated['subject_id'])) {
            $validated['subject_id'] = null;
        }

        $validated['teacher_user_id'] = auth()->id();

        return $validated;
    }

    protected function assertVisible(Homework $homework): void
    {
        $user = auth()->user();
        if ($user) {
            $this->homeworkService->assertUserCanAccessHomework($user, $homework);

            return;
        }

        abort(404);
    }

    protected function ensureCanView(): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can('homework.view'))) {
            return;
        }

        abort(403, 'Unauthorized.');
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
        if (! $instituteId) {
            return collect();
        }

        return BranchScope::for()->branchesForInstitute($instituteId);
    }

    protected function resolveInstituteId(): ?int
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return (int) $user->institute_id;
        }

        return null;
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
