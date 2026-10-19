<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Institute;
use App\Models\ResultAnnouncement;
use App\Services\ExamService;
use App\Services\ResultAnnouncementService;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Support\ParentBatchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResultAnnouncementController extends AdminController
{
    public function __construct(
        protected ResultAnnouncementService $resultService,
        protected ExamService $examService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCanView();
        $user = auth()->user();
        $studentId = $user && $user->isParent() ? (int) $request->query('student_id', 0) ?: null : null;
        $isParent = $user && $user->isParent();

        $announcements = $this->resultService->listForUser($user, $studentId, ! $isParent);
        $filters = [];

        if (! $isParent) {
            $filters = $this->listFilterVars($request, ['branches', 'batches']);

            if ($this->isPlatform() && $request->filled('institute_id')) {
                $filterId = (int) $request->input('institute_id');
                $announcements = $announcements
                    ->filter(fn ($item) => (int) $item->institute_id === $filterId)
                    ->values();
            }

            if ($request->filled('branch_id')) {
                $branchId = (int) $request->input('branch_id');
                $announcements = $announcements
                    ->filter(fn ($item) => (int) ($item->exam?->branch_id) === $branchId)
                    ->values();
            }

            if ($request->filled('batch_id')) {
                $batchId = (int) $request->input('batch_id');
                $announcements = $announcements
                    ->filter(fn ($item) => (int) ($item->exam?->batch_id) === $batchId)
                    ->values();
            }
        }

        return view('admin.result_announcement.result_announcement', array_merge($filters, [
            'announcements' => $announcements,
            'isParentView' => $isParent,
            'parentStudents' => $isParent ? ParentBatchScope::studentOptions($user) : collect(),
            'selectedStudentId' => $studentId,
            'canAdd' => $this->userCan('result_announcement.add'),
            'canShow' => $this->userCan('result_announcement.show'),
            'canExport' => $this->userCan('result_announcement.export'),
            'canEdit' => $this->userCan('result_announcement.edit'),
            'canDelete' => $this->userCan('result_announcement.delete'),
        ]));
    }

    public function create(): View
    {
        $this->ensureCan('result_announcement.add');

        return view('admin.result_announcement.add_result_announcement', [
            'announcement' => null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($this->resolveInstituteId()),
            'defaultInstituteId' => $this->resolveInstituteId(),
            'defaultBranchId' => null,
            'defaultBatchId' => null,
            'defaultExamId' => null,
        ]);
    }

    public function edit(ResultAnnouncement $resultAnnouncement): View
    {
        $this->ensureCan('result_announcement.edit');
        $this->assertVisible($resultAnnouncement);

        $resultAnnouncement->loadMissing(['exam.batch:id,name', 'exam.branch:id,name', 'exam.subject:id,name']);

        return view('admin.result_announcement.add_result_announcement', [
            'announcement' => $resultAnnouncement,
            'institutes' => collect(),
            'branches' => collect(),
            'defaultInstituteId' => $resultAnnouncement->institute_id,
            'defaultBranchId' => $resultAnnouncement->exam?->branch_id,
            'defaultBatchId' => $resultAnnouncement->exam?->batch_id,
            'defaultExamId' => $resultAnnouncement->exam_id,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCanAny(['result_announcement.add', 'result_announcement.edit']);

        $totalMarks = Exam::query()->find((int) $request->input('exam_id'))?->total_marks;

        $validated = $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'result_date' => ['nullable', 'date'],
            'publish' => ['sometimes', 'boolean'],
            'results' => ['required', 'array', 'min:1'],
            'results.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'results.*.marks_obtained' => [
                'nullable',
                'numeric',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail) use ($totalMarks) {
                    if ($value !== null && $totalMarks !== null && (float) $value > (float) $totalMarks) {
                        $fail("Obtained marks cannot exceed the maximum marks ({$totalMarks}).");
                    }
                },
            ],
            'results.*.remark' => ['nullable', 'string', 'max:255'],
            'results.*.is_attempted' => ['sometimes', 'boolean'],
            'results.*.attendance_status' => ['nullable', Rule::in(['present', 'absent'])],
        ]);

        $exam = Exam::query()->findOrFail((int) $validated['exam_id']);
        $this->examService->assertUserCanAccess(auth()->user(), $exam);

        $isUpdate = ResultAnnouncement::query()->where('exam_id', $exam->id)->exists();
        if (! $this->userCan($isUpdate ? 'result_announcement.edit' : 'result_announcement.add')) {
            abort(403, $isUpdate
                ? 'Result is already announced for this exam. You do not have permission to edit it.'
                : 'You do not have permission to add a result announcement.');
        }

        $announcement = $this->resultService->saveForExam(
            $exam,
            $validated['results'],
            $validated['result_date'] ?? null,
            $request->boolean('publish', true)
        );

        $message = $isUpdate ? 'Result updated.' : 'Result saved.';

        if ($request->expectsJson()) {
            return Ajax::success($message, $announcement, $isUpdate ? 200 : 201);
        }

        return redirect()->route('admin.result_announcement')->with('success', $message);
    }

    public function preview(Request $request, ResultAnnouncement $resultAnnouncement): JsonResponse
    {
        $this->ensureCan('result_announcement.show');
        $this->assertVisible($resultAnnouncement);

        $user = auth()->user();
        $sheet = $this->examService->resultSheet($resultAnnouncement->exam, true);

        $present = $sheet['present'];
        $absent = $sheet['absent'];

        if ($user && $user->isParent()) {
            $studentId = (int) $request->query('student_id', 0) ?: null;
            $childIds = ParentBatchScope::studentOptions($user)->pluck('id');

            if ($studentId) {
                $childIds = $childIds->intersect([(int) $studentId])->values();
            }

            $present = $present->filter(fn ($row) => $childIds->contains($row['student']->id))->values();
            $absent = $absent->filter(fn ($row) => $childIds->contains($row['student']->id))->values();
        }

        return Ajax::success('OK', [
            'announcement' => $resultAnnouncement,
            'exam' => $sheet['exam'],
            'present' => $present->map(fn ($row) => [
                'roll_no' => $row['roll_no'],
                'name' => $row['student']->name,
                'marks_obtained' => $row['result']?->marks_obtained,
                'remark' => $row['result']?->remark,
                'is_attempted' => $row['result']?->is_attempted,
                'attendance_status' => $row['result']?->attendance_status,
            ])->values(),
            'absent' => $absent->map(fn ($row) => [
                'roll_no' => $row['roll_no'],
                'name' => $row['student']->name,
                'remark' => $row['result']?->remark,
            ])->values(),
        ]);
    }

    public function destroy(Request $request, ResultAnnouncement $resultAnnouncement): JsonResponse|RedirectResponse
    {
        $this->ensureCan('result_announcement.delete');
        $this->assertVisible($resultAnnouncement);
        $this->resultService->delete($resultAnnouncement);

        return $this->respondDestroyed($request, 'Result announcement deleted.', 'admin.result_announcement');
    }

    public function exportPdf(Request $request, ResultAnnouncement $resultAnnouncement): Response
    {
        $this->ensureCan('result_announcement.export');
        $this->assertVisible($resultAnnouncement);

        $user = auth()->user();
        $sheet = $this->examService->resultSheet($resultAnnouncement->exam, true);

        $present = $sheet['present'];
        $absent = $sheet['absent'];

        if ($user && $user->isParent()) {
            $studentId = (int) $request->query('student_id', 0) ?: null;
            $childIds = ParentBatchScope::studentOptions($user)->pluck('id');

            if ($studentId) {
                $childIds = $childIds->intersect([(int) $studentId])->values();
            }

            $present = $present->filter(fn ($row) => $childIds->contains($row['student']->id))->values();
            $absent = $absent->filter(fn ($row) => $childIds->contains($row['student']->id))->values();
        } elseif ($request->filled('student_id')) {
            $studentId = (int) $request->query('student_id');
            $present = $present->filter(fn ($row) => (int) $row['student']->id === $studentId)->values();
            $absent = $absent->filter(fn ($row) => (int) $row['student']->id === $studentId)->values();
        }

        $announcement = $resultAnnouncement->loadMissing(['institute:id,name']);
        $exam = $sheet['exam'];
        $exam->loadMissing(['institute:id,name', 'branch:id,name', 'batch.institute:id,name', 'subject:id,name']);

        $safeTitle = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $exam->title ?? $announcement->title ?? 'result') ?: 'result';
        $filename = $safeTitle.'-result-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.result_announcement', compact('announcement', 'exam', 'present', 'absent'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    protected function assertVisible(ResultAnnouncement $announcement): void
    {
        $user = auth()->user();
        if ($user) {
            $this->resultService->assertUserCanAccess($user, $announcement);

            return;
        }

        abort(404);
    }

    protected function ensureCanView(): void
    {
        $this->ensureCan('result_announcement.view');
    }

    protected function userCan(string $permission): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isPlatformUser() || $user->can($permission)));
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
