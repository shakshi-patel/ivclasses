<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Institute;
use App\Models\StudyMaterial;
use App\Services\StudyMaterialService;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Support\ParentBatchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudyMaterialController extends AdminController
{
    public function __construct(
        protected StudyMaterialService $studyMaterialService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCanView();

        $user = auth()->user();
        $isParent = $user && $user->isParent();
        $studentId = $isParent ? (int) $request->query('student_id', 0) ?: null : null;

        $query = $this->studyMaterialService->queryForUser($user, $studentId)
            ->with(['batch:id,name', 'subject:id,name', 'creator:id,name', 'branch:id,name']);

        if (! $isParent) {
            $this->applyInstituteFilter($query, $request);
            if ($request->filled('branch_id')) {
                $query->where('branch_id', (int) $request->input('branch_id'));
            }
            if ($request->filled('batch_id')) {
                $query->where('batch_id', (int) $request->input('batch_id'));
            }
        }

        $materials = $query->orderByDesc('created_at')->orderByDesc('id')->get();
        $parentStudents = $isParent ? ParentBatchScope::studentOptions($user) : collect();
        $filters = $isParent ? ['isPlatform' => false, 'institutes' => collect()] : $this->listFilterVars($request, ['branches', 'batches']);

        return view('admin.study_material.study_material', array_merge([
            'materials' => $materials,
            'isParentView' => $isParent,
            'parentStudents' => $parentStudents,
            'selectedStudentId' => $studentId,
            'canAdd' => $user && ($user->isPlatformUser() || $user->can('study_material.add')),
            'canEdit' => $user && ($user->isPlatformUser() || $user->can('study_material.edit')),
            'canDelete' => $user && ($user->isPlatformUser() || $user->can('study_material.delete')),
            'canShow' => $user && ($user->isPlatformUser() || $user->can('study_material.show')),
            'canDownload' => $user && ($user->isPlatformUser() || $user->can('study_material.download')),
        ], $filters));
    }

    public function create(Request $request): View
    {
        $this->ensureCan('study_material.add');

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

        return view('admin.study_material.add_study_material', [
            'studyMaterial' => null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($instituteId),
            'defaultInstituteId' => $instituteId,
            'defaultBranchId' => $defaultBranchId,
            'defaultBatchId' => $defaultBatchId,
            'defaultSubjectId' => null,
        ]);
    }

    public function edit(StudyMaterial $studyMaterial): View
    {
        $this->ensureCan('study_material.edit');
        $this->assertVisible($studyMaterial);

        $studyMaterial->load(['batch:id,name,branch_id,institute_id']);

        return view('admin.study_material.add_study_material', [
            'studyMaterial' => $studyMaterial,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($studyMaterial->institute_id),
            'defaultInstituteId' => $studyMaterial->institute_id,
            'defaultBranchId' => $studyMaterial->branch_id,
            'defaultBatchId' => $studyMaterial->batch_id,
            'defaultSubjectId' => $studyMaterial->subject_id,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('study_material.add');

        $validated = $this->validateStudyMaterial($request);
        $studyMaterial = $this->studyMaterialService->create($validated, $request->file('file'));

        if ($request->expectsJson()) {
            return Ajax::success('Study material saved.', $studyMaterial, 201);
        }

        return redirect()->route('admin.study_material')->with('success', 'Study material saved.');
    }

    public function update(Request $request, StudyMaterial $studyMaterial): JsonResponse|RedirectResponse
    {
        $this->ensureCan('study_material.edit');
        $this->assertVisible($studyMaterial);

        $validated = $this->validateStudyMaterial($request, $studyMaterial);
        $studyMaterial = $this->studyMaterialService->update(
            $studyMaterial,
            $validated,
            $request->file('file'),
            $request->boolean('remove_file')
        );

        if ($request->expectsJson()) {
            return Ajax::success('Study material updated.', $studyMaterial);
        }

        return redirect()->route('admin.study_material')->with('success', 'Study material updated.');
    }

    public function destroy(Request $request, StudyMaterial $studyMaterial): JsonResponse|RedirectResponse
    {
        $this->ensureCan('study_material.delete');
        $this->assertVisible($studyMaterial);

        $this->studyMaterialService->delete($studyMaterial);

        return $this->respondDestroyed($request, 'Study material deleted.', 'admin.study_material');
    }

    public function download(StudyMaterial $studyMaterial): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        $this->ensureCan('study_material.download');
        $this->assertVisible($studyMaterial);

        if (! $studyMaterial->file_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($studyMaterial->file_path)) {
            abort(404, 'File not found.');
        }

        $name = $studyMaterial->file_name ?: basename($studyMaterial->file_path);

        return \Illuminate\Support\Facades\Storage::disk('public')->download($studyMaterial->file_path, $name);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateStudyMaterial(Request $request, ?StudyMaterial $studyMaterial = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        $hasExistingFile = $studyMaterial && $studyMaterial->file_path;

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
            'description' => ['nullable', 'string', 'max:5000'],
            'file' => [
                (! $hasExistingFile || $request->boolean('remove_file')) ? 'required' : 'nullable',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png,zip',
            ],
            'remove_file' => ['sometimes', 'boolean'],
        ];

        $validated = $request->validate($rules);

        if (empty($validated['subject_id'])) {
            $validated['subject_id'] = null;
        }

        return $validated;
    }

    protected function assertVisible(StudyMaterial $studyMaterial): void
    {
        $user = auth()->user();
        if ($user) {
            $this->studyMaterialService->assertUserCanAccess($user, $studyMaterial);

            return;
        }

        abort(404);
    }

    protected function ensureCanView(): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can('study_material.view'))) {
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
