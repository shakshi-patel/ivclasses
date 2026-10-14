<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\Institute;
use App\Services\ExamService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends AdminController
{
    public function __construct(
        protected ExamService $examService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCanView();
        $user = auth()->user();

        $query = $this->examService->queryForUser($user);

        $this->applyInstituteFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', (int) $request->input('batch_id'));
        }

        $exams = $query
            ->with(['batch:id,name', 'subject:id,name', 'branch:id,name', 'creator:id,name', 'announcement:id,exam_id,published_at'])
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->get();

        $filters = $this->listFilterVars($request, ['branches', 'batches']);

        return view('admin.exam.exam', array_merge([
            'exams' => $exams,
            'canAdd' => $user && ($user->isPlatformUser() || $user->can('exam.add')),
            'canShow' => $user && ($user->isPlatformUser() || $user->can('exam.show')),
            'canDownload' => $user && ($user->isPlatformUser() || $user->can('exam.download')),
            'canEdit' => $user && ($user->isPlatformUser() || $user->can('exam.edit')),
            'canDelete' => $user && ($user->isPlatformUser() || $user->can('exam.delete')),
        ], $filters));
    }

    public function create(Request $request): View
    {
        $this->ensureCan('exam.add');

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

        return view('admin.exam.add_exam', [
            'exam' => null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($instituteId),
            'defaultInstituteId' => $instituteId,
            'defaultBranchId' => $defaultBranchId,
            'defaultBatchId' => $defaultBatchId,
            'defaultSubjectId' => null,
        ]);
    }

    public function edit(Exam $exam): View
    {
        $this->ensureCan('exam.edit');
        $this->assertVisible($exam);
        $exam->load(['batch:id,name,branch_id,institute_id']);

        return view('admin.exam.add_exam', [
            'exam' => $exam,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($exam->institute_id),
            'defaultInstituteId' => $exam->institute_id,
            'defaultBranchId' => $exam->branch_id,
            'defaultBatchId' => $exam->batch_id,
            'defaultSubjectId' => $exam->subject_id,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('exam.add');
        $exam = $this->examService->create($this->validateExam($request), $request->file('attachment'));

        if ($request->expectsJson()) {
            return Ajax::success('Exam saved.', $exam, 201);
        }

        return redirect()->route('admin.exam')->with('success', 'Exam saved.');
    }

    public function update(Request $request, Exam $exam): JsonResponse|RedirectResponse
    {
        $this->ensureCan('exam.edit');
        $this->assertVisible($exam);

        $exam = $this->examService->update(
            $exam,
            $this->validateExam($request, $exam),
            $request->file('attachment'),
            $request->boolean('remove_attachment')
        );

        if ($request->expectsJson()) {
            return Ajax::success('Exam updated.', $exam);
        }

        return redirect()->route('admin.exam')->with('success', 'Exam updated.');
    }

    public function destroy(Request $request, Exam $exam): JsonResponse|RedirectResponse
    {
        $this->ensureCan('exam.delete');
        $this->assertVisible($exam);
        $this->examService->delete($exam);

        return $this->respondDestroyed($request, 'Exam deleted.', 'admin.exam');
    }

    public function download(Exam $exam): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        $this->ensureCan('exam.download');
        $this->assertVisible($exam);

        if (! $exam->attachment_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($exam->attachment_path)) {
            abort(404, 'File not found.');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($exam->attachment_path, basename($exam->attachment_path));
    }

    public function resultSheet(Exam $exam): JsonResponse
    {
        $this->ensureCanAny(['result_announcement.add', 'result_announcement.edit']);
        $this->assertVisible($exam);

        $sheet = $this->examService->resultSheet($exam);

        return Ajax::success('OK', [
            'exam' => [
                'id' => $sheet['exam']->id,
                'title' => $sheet['exam']->title,
                'subject' => $sheet['exam']->subject?->name,
                'duration_label' => $sheet['exam']->duration_label,
                'total_marks' => $sheet['exam']->total_marks,
                'exam_date' => $sheet['exam']->exam_date?->format('Y-m-d'),
                'exam_date_label' => $sheet['exam']->exam_date?->format('d M Y'),
            ],
            'attendance_marked' => $sheet['attendance_marked'],
            'present' => $sheet['present']->map(fn ($row) => [
                'student_id' => $row['student']->id,
                'roll_no' => $row['roll_no'],
                'name' => $row['student']->name,
                'standard' => $row['student']->standard?->name,
                'marks_obtained' => $row['result']?->marks_obtained,
                'remark' => $row['result']?->remark,
                'is_attempted' => $row['result']?->exists ? (bool) $row['result']->is_attempted : true,
                'attendance_status' => 'present',
            ])->values(),
            'absent' => $sheet['absent']->map(fn ($row) => [
                'student_id' => $row['student']->id,
                'roll_no' => $row['roll_no'],
                'name' => $row['student']->name,
                'standard' => $row['student']->standard?->name,
                'remark' => $row['result']?->remark ?: 'Absent',
            ])->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateExam(Request $request, ?Exam $exam = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        if (! $request->filled('subject_id')) {
            $request->merge(['subject_id' => null]);
        }

        $hasExistingFile = $exam && $exam->attachment_path;

        $validated = $request->validate([
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
            'exam_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'duration_label' => ['nullable', 'string', 'max:50'],
            'total_marks' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'attachment' => [
                (! $hasExistingFile && ! $request->boolean('remove_attachment')) ? 'nullable' : 'nullable',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,jpg,jpeg,png,zip',
            ],
            'remove_attachment' => ['sometimes', 'boolean'],
        ]);

        if (empty($validated['subject_id'])) {
            $validated['subject_id'] = null;
        }

        return $validated;
    }

    protected function assertVisible(Exam $exam): void
    {
        $user = auth()->user();
        if ($user) {
            $this->examService->assertUserCanAccess($user, $exam);

            return;
        }

        abort(404);
    }

    protected function ensureCanView(): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can('exam.view'))) {
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

        return $user && ! $user->isPlatformUser() ? (int) $user->institute_id : null;
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
