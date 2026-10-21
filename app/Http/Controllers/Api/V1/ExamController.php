<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ExamResource;
use App\Models\Exam;
use App\Services\ExamService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends ApiController
{
    public function __construct(
        protected ExamService $examService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('exam.view');

        $query = $this->examService->queryForUser($this->actor(), $this->parentStudentId())
            ->with(['batch:id,name', 'subject:id,name', 'branch:id,name', 'announcement:id,exam_id,published_at']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('batch_id')) {
            $query->where('batch_id', (int) $request->input('batch_id'));
        }

        return $this->paginated(
            'OK',
            $query->orderByDesc('exam_date')->orderByDesc('id')->paginate($this->perPage())
                ->through(fn (Exam $exam) => (new ExamResource($exam))->resolve($request))
        );
    }

    public function show(Exam $exam): JsonResponse
    {
        $this->ensureCanAny(['exam.show', 'exam.view']);
        $this->examService->assertUserCanAccess($this->actor(), $exam);

        $exam->load(['batch:id,name', 'subject:id,name', 'branch:id,name', 'announcement:id,exam_id,published_at']);

        return Ajax::success('OK', (new ExamResource($exam))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('exam.add');

        $exam = $this->examService->create($this->validateExam($request), $request->file('attachment'));
        $exam->load(['batch:id,name', 'subject:id,name', 'branch:id,name']);

        return Ajax::success('Exam saved.', (new ExamResource($exam))->resolve(), 201);
    }

    public function update(Request $request, Exam $exam): JsonResponse
    {
        $this->ensureCan('exam.edit');
        $this->examService->assertUserCanAccess($this->actor(), $exam);

        $exam = $this->examService->update(
            $exam,
            $this->validateExam($request, $exam),
            $request->file('attachment'),
            $request->boolean('remove_attachment')
        );
        $exam->load(['batch:id,name', 'subject:id,name', 'branch:id,name']);

        return Ajax::success('Exam updated.', (new ExamResource($exam))->resolve());
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $this->ensureCan('exam.delete');
        $this->examService->assertUserCanAccess($this->actor(), $exam);
        $this->examService->delete($exam);

        return Ajax::success('Exam deleted.');
    }

    public function download(Exam $exam): StreamedResponse
    {
        $this->ensureCan('exam.download');
        $this->examService->assertUserCanAccess($this->actor(), $exam);

        if (! $exam->attachment_path || ! Storage::disk('public')->exists($exam->attachment_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download($exam->attachment_path, basename($exam->attachment_path));
    }

    public function resultSheet(Exam $exam): JsonResponse
    {
        $this->ensureCanAny(['result_announcement.add', 'result_announcement.edit']);
        $this->examService->assertUserCanAccess($this->actor(), $exam);

        $sheet = $this->examService->resultSheet($exam);
        $exam->load(['batch:id,name', 'subject:id,name', 'branch:id,name', 'announcement:id,exam_id,published_at']);

        return Ajax::success('OK', [
            'exam' => (new ExamResource($exam))->resolve(),
            'attendance_marked' => (bool) $sheet['attendance_marked'],
            'present' => $sheet['present']->map(fn ($row) => [
                'student_id' => $row['student']->id,
                'name' => $row['student']->name,
                'roll_no' => $row['roll_no'],
                'marks_obtained' => $row['result']?->marks_obtained !== null ? (float) $row['result']->marks_obtained : null,
                'remark' => $row['result']?->remark,
                'is_attempted' => $row['result']?->is_attempted,
                'attendance_status' => $row['result']?->attendance_status,
            ])->values()->all(),
            'absent' => $sheet['absent']->map(fn ($row) => [
                'student_id' => $row['student']->id,
                'name' => $row['student']->name,
                'roll_no' => $row['roll_no'],
                'marks_obtained' => $row['result']?->marks_obtained !== null ? (float) $row['result']->marks_obtained : null,
                'remark' => $row['result']?->remark,
                'is_attempted' => $row['result']?->is_attempted,
                'attendance_status' => $row['result']?->attendance_status,
            ])->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateExam(Request $request, ?Exam $exam = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);
        if (! $request->filled('subject_id')) {
            $request->merge(['subject_id' => null]);
        }

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
            'exam_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'duration_label' => ['nullable', 'string', 'max:50'],
            'total_marks' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png,zip'],
            'remove_attachment' => ['sometimes', 'boolean'],
        ]);

        $validated['subject_id'] = $validated['subject_id'] ?: null;

        return $validated;
    }
}
