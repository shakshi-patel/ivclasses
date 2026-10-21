<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\StudyMaterialResource;
use App\Models\StudyMaterial;
use App\Services\StudyMaterialService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudyMaterialController extends ApiController
{
    public function __construct(
        protected StudyMaterialService $studyMaterialService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('study_material.view');

        $studentId = $this->actor()->isParent() ? (int) $request->query('student_id', 0) ?: null : null;
        $query = $this->studyMaterialService->queryForUser($this->actor(), $studentId)
            ->with(['batch:id,name', 'subject:id,name', 'creator:id,name', 'branch:id,name']);

        if (! $this->actor()->isParent()) {
            if ($request->filled('branch_id')) {
                $query->where('branch_id', (int) $request->input('branch_id'));
            }
            if ($request->filled('batch_id')) {
                $query->where('batch_id', (int) $request->input('batch_id'));
            }
        }

        return $this->paginated(
            'OK',
            $query->orderByDesc('created_at')->orderByDesc('id')->paginate($this->perPage())
                ->through(fn (StudyMaterial $row) => (new StudyMaterialResource($row))->resolve($request))
        );
    }

    public function show(StudyMaterial $studyMaterial): JsonResponse
    {
        $this->ensureCanAny(['study_material.show', 'study_material.view']);
        $this->studyMaterialService->assertUserCanAccess($this->actor(), $studyMaterial);

        $studyMaterial->load(['batch:id,name', 'subject:id,name', 'creator:id,name', 'branch:id,name']);

        return Ajax::success('OK', (new StudyMaterialResource($studyMaterial))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('study_material.add');

        $validated = $this->validateStudyMaterial($request);
        $file = $request->file('file');
        if (! $file) {
            return Ajax::error('A file is required.', null, 422);
        }

        $studyMaterial = $this->studyMaterialService->create($validated, $file);
        $studyMaterial->load(['batch:id,name', 'subject:id,name', 'creator:id,name', 'branch:id,name']);

        return Ajax::success('Study material saved.', (new StudyMaterialResource($studyMaterial))->resolve(), 201);
    }

    public function update(Request $request, StudyMaterial $studyMaterial): JsonResponse
    {
        $this->ensureCan('study_material.edit');
        $this->studyMaterialService->assertUserCanAccess($this->actor(), $studyMaterial);

        $studyMaterial = $this->studyMaterialService->update(
            $studyMaterial,
            $this->validateStudyMaterial($request, $studyMaterial),
            $request->file('file'),
            $request->boolean('remove_file')
        );
        $studyMaterial->load(['batch:id,name', 'subject:id,name', 'creator:id,name', 'branch:id,name']);

        return Ajax::success('Study material updated.', (new StudyMaterialResource($studyMaterial))->resolve());
    }

    public function destroy(StudyMaterial $studyMaterial): JsonResponse
    {
        $this->ensureCan('study_material.delete');
        $this->studyMaterialService->assertUserCanAccess($this->actor(), $studyMaterial);
        $this->studyMaterialService->delete($studyMaterial);

        return Ajax::success('Study material deleted.');
    }

    public function download(StudyMaterial $studyMaterial): StreamedResponse
    {
        $this->ensureCan('study_material.download');
        $this->studyMaterialService->assertUserCanAccess($this->actor(), $studyMaterial);

        if (! $studyMaterial->file_path || ! Storage::disk('public')->exists($studyMaterial->file_path)) {
            abort(404, 'File not found.');
        }

        $name = $studyMaterial->file_name ?: basename($studyMaterial->file_path);

        return Storage::disk('public')->download($studyMaterial->file_path, $name);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateStudyMaterial(Request $request, ?StudyMaterial $studyMaterial = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        $hasExistingFile = $studyMaterial && $studyMaterial->file_path;

        $validated = $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'batch_id' => array_values(array_filter([
                'required',
                'integer',
                Rule::exists('batches', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)),
                $this->actor()->isTeacher()
                    ? Rule::in(\App\Support\OwnBatchScope::batchIds($this->actor()) ?: [0])
                    : null,
            ])),
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
        ]);

        $validated['subject_id'] = $validated['subject_id'] ?? null;

        return $validated;
    }
}
