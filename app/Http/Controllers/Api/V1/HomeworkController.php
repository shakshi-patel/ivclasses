<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\HomeworkResource;
use App\Models\Homework;
use App\Services\HomeworkService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeworkController extends ApiController
{
    public function __construct(
        protected HomeworkService $homeworkService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('homework.view');

        $studentId = $this->actor()->isParent() ? (int) $request->query('student_id', 0) ?: null : null;
        $query = $this->homeworkService->queryForUser($this->actor(), $studentId)
            ->with(['batch:id,name', 'subject:id,name', 'branch:id,name', 'teacher:id,name']);

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
            $query->orderByDesc('due_date')->orderByDesc('id')->paginate($this->perPage())
                ->through(fn (Homework $homework) => (new HomeworkResource($homework))->resolve($request))
        );
    }

    public function show(Homework $homework): JsonResponse
    {
        $this->ensureCanAny(['homework.show', 'homework.view']);
        $this->homeworkService->assertUserCanAccessHomework($this->actor(), $homework);

        $homework->load(['batch:id,name', 'subject:id,name', 'branch:id,name', 'teacher:id,name']);

        return Ajax::success('OK', (new HomeworkResource($homework))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('homework.add');

        $homework = $this->homeworkService->create($this->validateHomework($request), $request->file('attachment'));
        $homework->load(['batch:id,name', 'subject:id,name', 'branch:id,name', 'teacher:id,name']);

        return Ajax::success('Homework saved.', (new HomeworkResource($homework))->resolve(), 201);
    }

    public function update(Request $request, Homework $homework): JsonResponse
    {
        $this->ensureCan('homework.edit');
        $this->homeworkService->assertUserCanAccessHomework($this->actor(), $homework);

        $homework = $this->homeworkService->update(
            $homework,
            $this->validateHomework($request, $homework),
            $request->file('attachment'),
            $request->boolean('remove_attachment')
        );
        $homework->load(['batch:id,name', 'subject:id,name', 'branch:id,name', 'teacher:id,name']);

        return Ajax::success('Homework updated.', (new HomeworkResource($homework))->resolve());
    }

    public function destroy(Homework $homework): JsonResponse
    {
        $this->ensureCan('homework.delete');
        $this->homeworkService->assertUserCanAccessHomework($this->actor(), $homework);
        $this->homeworkService->delete($homework);

        return Ajax::success('Homework deleted.');
    }

    public function download(Homework $homework): StreamedResponse
    {
        $this->ensureCan('homework.download');
        $this->homeworkService->assertUserCanAccessHomework($this->actor(), $homework);

        if (! $homework->attachment_path || ! Storage::disk('public')->exists($homework->attachment_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download($homework->attachment_path, basename($homework->attachment_path));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateHomework(Request $request, ?Homework $homework = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        $type = $request->input('type', Homework::TYPE_TEXT);
        $needsFile = in_array($type, [Homework::TYPE_FILE, Homework::TYPE_FILE_TEXT], true);
        $hasExistingFile = $homework && $homework->attachment_path;

        $rules = [
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
        $validated['subject_id'] = $validated['subject_id'] ?? null;
        $validated['teacher_user_id'] = $this->actor()->id;

        return $validated;
    }
}
