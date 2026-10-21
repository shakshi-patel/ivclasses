<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\StudentResource;
use App\Models\Standard;
use App\Models\Student;
use App\Services\StudentService;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Support\ParentBatchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class StudentController extends ApiController
{
    public function __construct(
        protected StudentService $studentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->actor()->isParent()) {
            $this->ensureCan('student.view');
        }

        $query = $this->studentQuery()
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['branch:id,name', 'standard:id,name', 'parent:id,father_name,phone,email']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('standard_id')) {
            $query->where('standard_id', (int) $request->input('standard_id'));
        }
        if ($request->filled('batch_id')) {
            $batchId = (int) $request->input('batch_id');
            $query->whereHas('enrollments', fn ($q) => $q->where('batch_id', $batchId)->where('status', 'active'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('admission_no', 'like', "%{$q}%");
            });
        }

        return $this->paginated(
            'OK',
            $query->latest('admission_date')->latest('id')->paginate($this->perPage())
                ->through(fn (Student $student) => (new StudentResource($student))->resolve($request))
        );
    }

    public function show(Student $student): JsonResponse
    {
        if (! $this->actor()->isParent()) {
            $this->ensureCanAny(['student.show', 'student.view']);
        }
        $this->assertVisible($student);

        $student->load([
            'branch:id,name',
            'standard:id,name',
            'parent:id,father_name,mother_name,phone,email',
            'enrollments' => fn ($q) => $q->where('status', 'active')->with(['batch:id,name', 'subject:id,name']),
        ]);

        return Ajax::success('OK', (new StudentResource($student))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('student.add');

        [$validated, $enrollments] = $this->validateStudent($request);
        $instituteId = (int) $validated['institute_id'];

        if ($limitError = $this->studentService->studentLimitError($instituteId)) {
            return Ajax::error($limitError, null, 403);
        }

        $validated['photo'] = $request->file('photo');
        $student = $this->studentService->create($validated, $enrollments);
        $student->load(['branch:id,name', 'standard:id,name', 'parent', 'enrollments.batch', 'enrollments.subject']);

        return Ajax::success('Student created.', (new StudentResource($student))->resolve(), 201);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $this->ensureCan('student.edit');
        $this->assertVisible($student);

        [$validated, $enrollments] = $this->validateStudent($request, $student);
        $validated['photo'] = $request->file('photo');
        $student = $this->studentService->update($student, $validated, $enrollments);
        $student->load(['branch:id,name', 'standard:id,name', 'parent', 'enrollments.batch', 'enrollments.subject']);

        return Ajax::success('Student updated.', (new StudentResource($student))->resolve());
    }

    public function status(Student $student): JsonResponse
    {
        $this->ensureCan('student.status');
        $this->assertVisible($student);

        $updated = $this->studentService->toggleStatus($student);

        return Ajax::success(
            $updated->status === 'active' ? 'Student activated.' : 'Student deactivated.',
            ['id' => $updated->id, 'status' => $updated->status]
        );
    }

    public function receipt(Student $student): JsonResponse
    {
        $this->ensureCan('student.receipt');
        $this->assertVisible($student);
        $this->loadStudentReceiptData($student);

        return Ajax::success('OK', array_merge(
            (new StudentResource($student))->resolve(),
            [
                'form_fees' => (float) ($student->form_fees ?? 0),
                'form_fees_payment_method' => $student->form_fees_payment_method,
                'pdf_url' => route('api.v1.students.receipt-pdf', $student),
            ]
        ));
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
            'parent:id,father_name,mother_name,phone,email',
            'institute',
            'enrollments' => fn ($q) => $q->where('status', 'active')
                ->with(['batch:id,name', 'batch.subjects:id,name', 'subject:id,name']),
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    protected function validateStudent(Request $request, ?Student $student = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        $user = $this->actor();
        $branchIds = $user->isTeacher()
            ? \App\Support\OwnBatchScope::branchIds($user)
            : BranchScope::for($user)->allowedBranchIdsForInstitute($instituteId);
        $standardIds = Standard::query()->where('institute_id', $instituteId)->pluck('id')->all();

        $validated = $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_id' => ['required', 'integer', Rule::in($branchIds ?: [0])],
            'name' => ['required', 'string', 'max:150'],
            'dob' => ['required', 'date'],
            'gender' => ['required', 'in:Male,Female,Other,male,female,other'],
            'medical_notes' => ['nullable', 'string', 'max:500'],
            'standard_id' => ['required', 'integer', Rule::in($standardIds ?: [0])],
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
            'father_phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'father_phone_2' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'parent_password' => ['nullable', 'string', 'min:8'],
            'status' => ['nullable', 'in:active,inactive,left,alumni'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'enrollments' => ['required', 'array', 'min:1'],
            'enrollments.*.batch_id' => array_values(array_filter([
                'required_with:enrollments',
                'integer',
                'exists:batches,id',
                $this->actor()->isTeacher()
                    ? Rule::in(\App\Support\OwnBatchScope::batchIds($this->actor()) ?: [0])
                    : null,
            ])),
            'enrollments.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'enrollments.*.roll_no' => ['nullable', 'string', 'max:30'],
            'enrollments.*.total_fees' => ['required_with:enrollments', 'numeric', 'min:0'],
            'enrollments.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $enrollments = $validated['enrollments'] ?? [];
        $validated['status'] = $validated['status'] ?? ($student?->status ?? 'active');

        return [$validated, $enrollments];
    }

    protected function studentQuery()
    {
        $user = $this->actor();
        $query = Student::query();

        if ($user->isParent()) {
            $ids = ParentBatchScope::studentIds($user);

            return $ids->isEmpty() ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids->all());
        }

        if ($user->isTeacher()) {
            $ids = \App\Support\OwnBatchScope::studentIds($user);

            return $ids->isEmpty() ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids->all());
        }

        $scope = BranchScope::for($user);
        if (! $scope->isRestricted()) {
            return $query;
        }

        $ids = $scope->branchIds();

        return $ids === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $ids);
    }

    protected function assertVisible(Student $student): void
    {
        $user = $this->actor();
        if ((int) $student->institute_id !== (int) $user->institute_id) {
            abort(404, 'Student not found.');
        }

        if ($user->isParent()) {
            ParentBatchScope::assertOwnsStudent($user, (int) $student->id);

            return;
        }

        if ($user->isTeacher()) {
            abort_unless(
                \App\Support\OwnBatchScope::studentIds($user)->contains((int) $student->id),
                404,
                'Student not found.'
            );

            return;
        }

        if ($student->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $student->branch_id);
        }
    }
}
