<?php

namespace App\Http\Controllers\Admin;

use App\Models\Institute;
use App\Models\Standard;
use App\Models\Student;
use App\Services\ExamService;
use App\Services\FeeService;
use App\Services\GstInvoiceService;
use App\Services\HomeworkService;
use App\Services\NoticeService;
use App\Services\ResultAnnouncementService;
use App\Services\StudentService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends AdminController
{
    public function __construct(
        protected StudentService $studentService,
        protected HomeworkService $homeworkService,
        protected ExamService $examService,
        protected FeeService $feeService,
        protected NoticeService $noticeService,
        protected GstInvoiceService $gstInvoiceService,
        protected ResultAnnouncementService $resultAnnouncementService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('student.view');

        $query = $this->studentQuery()
            ->with(['branch:id,name', 'standard:id,name', 'parent:id,father_name,phone']);

        $this->applyInstituteFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('standard_id')) {
            $query->where('standard_id', (int) $request->input('standard_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $students = $query->latest('admission_date')->latest('id')->get();
        $filters = $this->listFilterVars($request, ['branches', 'standards']);

        return view('admin.master.student.student', array_merge(compact('students'), $filters));
    }

    public function create(): View
    {
        $this->ensureCan('student.add');

        $instituteId = $this->currentInstituteId();
        if (! $instituteId && auth()->user()?->isPlatformUser()) {
            $instituteId = (int) old('institute_id', $this->instituteOptions()->first()?->id ?: 0) ?: null;
        }

        return view('admin.master.student.add_student', [
            'student' => null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($instituteId),
            'standards' => $this->standardOptions($instituteId),
            'batches' => $instituteId ? $this->studentService->batchesWithSubjects($instituteId) : [],
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('student.add');

        [$validated, $enrollments] = $this->validateStudent($request);
        $instituteId = (int) $validated['institute_id'];

        if ($limitError = $this->studentService->studentLimitError($instituteId)) {
            if ($request->expectsJson()) {
                return Ajax::error($limitError, null, 403);
            }

            return back()->withInput()->withErrors(['institute_id' => $limitError]);
        }

        $validated['photo'] = $request->file('photo');
        $student = $this->studentService->create($validated, $enrollments);

        if ($request->expectsJson()) {
            return Ajax::success('Student created.', $student, 201);
        }

        return redirect()->route('admin.student')->with('success', 'Student created.');
    }

    public function show(Student $student): View
    {
        $this->ensureCan('student.show');
        $this->assertVisible($student);

        $student->load([
            'branch',
            'standard',
            'parent.user',
            'enrollments' => fn ($q) => $q->where('status', 'active')->with(['batch.branch', 'subject']),
        ]);

        $activeEnrollments = $student->enrollments;
        $firstEnrollment = $activeEnrollments->first() ?? $student->enrollments->first();
        $studentHomeworks = $this->homeworkService->listForStudent($student);
        $studentExams = $this->examService->listForStudent($student);
        $studentFeeProfile = $this->feeService->profileForStudent($student);
        $studentNotices = $this->noticeService->listForStudent($student);
        $studentGstInvoices = $this->gstInvoiceService->listForStudent($student);
        $studentResults = $this->resultAnnouncementService->listForStudent($student);

        return view('admin.master.student.view_student', [
            'student' => $student,
            'activeEnrollments' => $activeEnrollments,
            'firstEnrollment' => $firstEnrollment,
            'rollNo' => $firstEnrollment?->roll_no ?? '—',
            'batchName' => $firstEnrollment?->batch?->name ?? '—',
            'defaultBatchId' => $firstEnrollment?->batch_id,
            'feeTotal' => (float) ($studentFeeProfile['total'] ?? 0),
            'feePaid' => (float) ($studentFeeProfile['paid'] ?? 0),
            'feeDue' => (float) ($studentFeeProfile['due'] ?? 0),
            'feePayments' => $studentFeeProfile['payments'] ?? collect(),
            'feeFollowUps' => $studentFeeProfile['followUps'] ?? collect(),
            'studentHomeworks' => $studentHomeworks,
            'studentExams' => $studentExams,
            'studentFeeProfile' => $studentFeeProfile,
            'studentNotices' => $studentNotices,
            'studentGstInvoices' => $studentGstInvoices,
            'studentResults' => $studentResults,
        ]);
    }

    public function edit(Student $student): View
    {
        $this->ensureCan('student.edit');
        $this->assertVisible($student);

        $student->load(['parent', 'enrollments']);

        return view('admin.master.student.add_student', [
            'student' => $student,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($student->institute_id),
            'standards' => $this->standardOptions($student->institute_id),
            'batches' => $this->studentService->batchesWithSubjects(
                (int) $student->institute_id,
                $student->branch_id
            ),
        ]);
    }

    public function update(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        $this->ensureCan('student.edit');
        $this->assertVisible($student);

        [$validated, $enrollments] = $this->validateStudent($request, $student);
        $validated['photo'] = $request->file('photo');
        $this->studentService->update($student, $validated, $enrollments);

        if ($request->expectsJson()) {
            return Ajax::success('Student updated.', $student->fresh(['branch', 'standard', 'parent', 'enrollments']));
        }

        return redirect()->route('admin.student')->with('success', 'Student updated.');
    }

    public function toggleStatus(Student $student): JsonResponse
    {
        $this->ensureCan('student.status');
        $this->assertVisible($student);

        $updated = $this->studentService->toggleStatus($student);

        return Ajax::success(
            $updated->status === 'active' ? 'Student activated.' : 'Student deactivated.',
            ['id' => $updated->id, 'status' => $updated->status]
        );
    }

    public function receipt(Request $request, Student $student): JsonResponse|View
    {
        $this->ensureCan('student.receipt');
        $this->assertVisible($student);

        $this->loadStudentReceiptData($student);

        if ($request->ajax() || $request->wantsJson()) {
            return Ajax::success('OK', [
                'title' => 'Student Receipt — '.($student->admission_no ?? $student->name),
                'html' => view('admin.student.partials.receipt_card', compact('student'))->render(),
                'export_url' => route('admin.student.receipt_pdf', $student),
            ]);
        }

        return view('admin.print.student_receipt', compact('student'));
    }

    public function receiptPdf(Student $student): Response
    {
        $this->ensureCan('student.receipt');
        $this->assertVisible($student);

        $this->loadStudentReceiptData($student);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $student->admission_no ?: $student->name) ?: 'student';
        $filename = $safeName.'-receipt-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.student_receipt', compact('student'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    protected function loadStudentReceiptData(Student $student): void
    {
        $student->load([
            'branch:id,name',
            'standard:id,name',
            'parent:id,father_name,phone',
            'institute',
            'enrollments' => fn ($q) => $q->where('status', 'active')
                ->with(['batch:id,name', 'batch.subjects:id,name', 'subject:id,name']),
        ]);
    }

    protected function studentQuery()
    {
        $scope = BranchScope::for();
        $query = Student::query();

        if (! $scope->isRestricted()) {
            return $query;
        }

        $ids = $scope->branchIds();
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $ids);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    protected function validateStudent(Request $request, ?Student $student = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        $branchIds = BranchScope::for()->allowedBranchIdsForInstitute($instituteId);
        $standardIds = Standard::query()
            ->where('institute_id', $instituteId)
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_id' => ['required', 'integer', Rule::in($branchIds)],
            'name' => ['required', 'string', 'max:150'],
            'dob' => ['required', 'date'],
            'gender' => ['required', 'in:Male,Female,Other,male,female,other'],
            'medical_notes' => ['nullable', 'string', 'max:500'],
            'standard_id' => ['required', 'integer', Rule::in($standardIds)],
            'aadhaar' => ['nullable', 'string', 'max:20'],
            'category' => ['required', 'in:General,OBC,SC,ST'],
            'admission_date' => ['required', 'date'],
            'school_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'form_fees' => ['nullable', 'numeric', 'min:0'],
            'form_fees_payment_method' => ['nullable', 'in:Cash,Online,UPI,Cheque'],
            'father_name' => ['required', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'father_email' => ['required', 'email', 'max:150'],
            'father_phone' => ['required', 'string', 'max:15'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'parent_password' => ['nullable', 'string', 'min:8'],
            'status' => ['nullable', 'in:active,inactive,left,alumni'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'enrollments' => ['required', 'array', 'min:1'],
            'enrollments.*.batch_id' => ['required_with:enrollments', 'integer', 'exists:batches,id'],
            'enrollments.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'enrollments.*.roll_no' => ['nullable', 'string', 'max:30'],
            'enrollments.*.total_fees' => ['required_with:enrollments', 'numeric', 'min:0'],
            'enrollments.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $enrollments = $validated['enrollments'] ?? [];
        $validated['status'] = $validated['status'] ?? ($student?->status ?? 'active');

        return [$validated, $enrollments];
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

    protected function standardOptions(?int $instituteId = null)
    {
        $user = auth()->user();
        $instituteId ??= $user && ! $user->isPlatformUser() ? $user->institute_id : null;

        if (! $instituteId) {
            return collect();
        }

        return Standard::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function currentInstituteId(): ?int
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return (int) $user->institute_id;
        }

        return null;
    }

    protected function assertVisible(Student $student): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $student->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($student->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $student->branch_id);
        }
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
