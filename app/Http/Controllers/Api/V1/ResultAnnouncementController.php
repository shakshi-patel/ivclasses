<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ExamResource;
use App\Http\Resources\Api\V1\ResultAnnouncementResource;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ResultAnnouncement;
use App\Services\ExamService;
use App\Services\ResultAnnouncementService;
use App\Support\Ajax;
use App\Support\ParentBatchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ResultAnnouncementController extends ApiController
{
    public function __construct(
        protected ResultAnnouncementService $resultService,
        protected ExamService $examService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('result_announcement.view');

        $user = $this->actor();
        $studentId = $user->isParent() ? (int) $request->query('student_id', 0) ?: null : null;
        $query = $user->isParent()
            ? $this->resultService->queryForUser($user, $studentId)
            : $this->resultService->adminQueryForUser($user);

        $query->with([
            'exam.batch:id,name',
            'exam.subject:id,name',
            'exam.branch:id,name',
            'creator:id,name',
        ]);

        if (! $user->isParent()) {
            if ($request->filled('branch_id')) {
                $branchId = (int) $request->input('branch_id');
                $query->whereHas('exam', fn ($q) => $q->where('branch_id', $branchId));
            }
            if ($request->filled('batch_id')) {
                $batchId = (int) $request->input('batch_id');
                $query->whereHas('exam', fn ($q) => $q->where('batch_id', $batchId));
            }
        }

        $paginator = $query->orderByDesc('result_date')->orderByDesc('id')->paginate($this->perPage());
        $this->attachChildResults($paginator->getCollection(), $user, $studentId);

        return $this->paginated(
            'OK',
            $paginator->through(fn (ResultAnnouncement $row) => (new ResultAnnouncementResource($row))->resolve($request))
        );
    }

    public function show(Request $request, ResultAnnouncement $resultAnnouncement): JsonResponse
    {
        $this->ensureCanAny(['result_announcement.show', 'result_announcement.view']);
        $this->resultService->assertUserCanAccess($this->actor(), $resultAnnouncement);

        $resultAnnouncement->load([
            'exam.batch:id,name',
            'exam.subject:id,name',
            'exam.branch:id,name',
            'creator:id,name',
        ]);

        $this->attachChildResults(collect([$resultAnnouncement]), $this->actor(), $this->parentStudentId());

        return Ajax::success('OK', (new ResultAnnouncementResource($resultAnnouncement))->resolve($request));
    }

    public function store(Request $request): JsonResponse
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
        $this->examService->assertUserCanAccess($this->actor(), $exam);

        $isUpdate = ResultAnnouncement::query()->where('exam_id', $exam->id)->exists();
        if (! $this->actor()->can($isUpdate ? 'result_announcement.edit' : 'result_announcement.add')) {
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
        $announcement->load([
            'exam.batch:id,name',
            'exam.subject:id,name',
            'exam.branch:id,name',
            'creator:id,name',
        ]);

        return Ajax::success(
            $isUpdate ? 'Result updated.' : 'Result saved.',
            (new ResultAnnouncementResource($announcement))->resolve($request),
            $isUpdate ? 200 : 201
        );
    }

    public function preview(Request $request, ResultAnnouncement $resultAnnouncement): JsonResponse
    {
        $this->ensureCanAny(['result_announcement.show', 'result_announcement.view']);
        $this->resultService->assertUserCanAccess($this->actor(), $resultAnnouncement);

        $sheet = $this->scopedSheet($resultAnnouncement, $request, true);
        $resultAnnouncement->load([
            'exam.batch:id,name',
            'exam.subject:id,name',
            'exam.branch:id,name',
            'creator:id,name',
        ]);

        return Ajax::success('OK', [
            'announcement' => (new ResultAnnouncementResource($resultAnnouncement))->resolve($request),
            'exam' => (new ExamResource($sheet['exam']->loadMissing(['batch:id,name', 'subject:id,name', 'branch:id,name'])))->resolve($request),
            'present' => $this->mapSheetRows($sheet['present']),
            'absent' => $this->mapSheetRows($sheet['absent']),
        ]);
    }

    public function exportPdf(Request $request, ResultAnnouncement $resultAnnouncement): Response
    {
        $this->ensureCan('result_announcement.export');
        $this->resultService->assertUserCanAccess($this->actor(), $resultAnnouncement);

        $sheet = $this->scopedSheet($resultAnnouncement, $request, true);
        $announcement = $resultAnnouncement->loadMissing(['institute:id,name']);
        $exam = $sheet['exam'];
        $exam->loadMissing(['institute:id,name', 'branch:id,name', 'batch.institute:id,name', 'subject:id,name']);
        $present = $sheet['present'];
        $absent = $sheet['absent'];

        $safeTitle = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $exam->title ?? $announcement->title ?? 'result') ?: 'result';
        $filename = $safeTitle.'-result-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.result_announcement', compact('announcement', 'exam', 'present', 'absent'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function destroy(ResultAnnouncement $resultAnnouncement): JsonResponse
    {
        $this->ensureCan('result_announcement.delete');
        $this->resultService->assertUserCanAccess($this->actor(), $resultAnnouncement);
        $this->resultService->delete($resultAnnouncement);

        return Ajax::success('Result announcement deleted.');
    }

    /**
     * @return array{exam: Exam, present: \Illuminate\Support\Collection, absent: \Illuminate\Support\Collection, attendance_marked: bool}
     */
    protected function scopedSheet(ResultAnnouncement $announcement, Request $request, bool $useSavedGrouping): array
    {
        $user = $this->actor();
        $sheet = $this->examService->resultSheet($announcement->exam, $useSavedGrouping);
        $present = $sheet['present'];
        $absent = $sheet['absent'];

        if ($user->isParent()) {
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

        $sheet['present'] = $present;
        $sheet['absent'] = $absent;

        return $sheet;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function mapSheetRows($rows): array
    {
        return $rows->map(fn ($row) => [
            'student_id' => $row['student']->id,
            'name' => $row['student']->name,
            'roll_no' => $row['roll_no'],
            'marks_obtained' => $row['result']?->marks_obtained !== null ? (float) $row['result']->marks_obtained : null,
            'remark' => $row['result']?->remark,
            'is_attempted' => $row['result']?->is_attempted,
            'attendance_status' => $row['result']?->attendance_status,
        ])->values()->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ResultAnnouncement>  $rows
     */
    protected function attachChildResults($rows, $user, ?int $studentId): void
    {
        if (! $user->isParent() || $rows->isEmpty()) {
            return;
        }

        $childId = $studentId ?: ParentBatchScope::studentIds($user)->first();
        if (! $childId) {
            return;
        }

        $map = ExamResult::query()
            ->where('student_id', $childId)
            ->whereIn('exam_id', $rows->pluck('exam_id'))
            ->get()
            ->keyBy('exam_id');

        foreach ($rows as $row) {
            $row->setRelation('studentResult', $map->get($row->exam_id));
        }
    }
}
