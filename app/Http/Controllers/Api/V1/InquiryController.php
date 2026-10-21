<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\InquiryResource;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\Inquiry;
use App\Models\Standard;
use App\Services\InquiryService;
use App\Services\StudentService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use RuntimeException;

class InquiryController extends ApiController
{
    public function __construct(
        protected InquiryService $inquiryService,
        protected StudentService $studentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('inquiry.view');

        $query = $this->inquiryQuery()
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['branch:id,name', 'standard:id,name']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('standard_id')) {
            $query->where('standard_id', (int) $request->input('standard_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('student_name', 'like', "%{$q}%")
                    ->orWhere('inquiry_code', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return $this->paginated(
            'OK',
            $query->latest('inquiry_date')->latest('id')->paginate($this->perPage())
                ->through(fn (Inquiry $inquiry) => (new InquiryResource($inquiry))->resolve($request))
        );
    }

    public function show(Inquiry $inquiry): JsonResponse
    {
        $this->ensureCanAny(['inquiry.show', 'inquiry.view']);
        $this->assertVisible($inquiry);

        $inquiry->load([
            'branch:id,name',
            'standard:id,name',
            'followUps.creator:id,name',
        ]);

        return Ajax::success('OK', (new InquiryResource($inquiry))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('inquiry.add');

        $validated = $this->validateInquiry($request);
        $validated['photo'] = $request->file('photo');
        $inquiry = $this->inquiryService->create($validated);
        $inquiry->load(['branch:id,name', 'standard:id,name', 'followUps.creator:id,name']);

        return Ajax::success('Inquiry created.', (new InquiryResource($inquiry))->resolve(), 201);
    }

    public function update(Request $request, Inquiry $inquiry): JsonResponse
    {
        $this->ensureCan('inquiry.edit');
        $this->assertVisible($inquiry);

        if ($inquiry->isConverted()) {
            return Ajax::error('Converted inquiries cannot be edited.', null, 422);
        }

        $validated = $this->validateInquiry($request, $inquiry);
        $validated['photo'] = $request->file('photo');

        try {
            $inquiry = $this->inquiryService->update($inquiry, $validated);
        } catch (RuntimeException $e) {
            return Ajax::error($e->getMessage(), null, 422);
        }

        $inquiry->load(['branch:id,name', 'standard:id,name', 'followUps.creator:id,name']);

        return Ajax::success('Inquiry updated.', (new InquiryResource($inquiry))->resolve());
    }

    public function destroy(Inquiry $inquiry): JsonResponse
    {
        $this->ensureCan('inquiry.delete');
        $this->assertVisible($inquiry);

        try {
            $this->inquiryService->delete($inquiry);
        } catch (RuntimeException $e) {
            return Ajax::error($e->getMessage(), null, 422);
        }

        return Ajax::success('Inquiry deleted.');
    }

    public function convertForm(Inquiry $inquiry): JsonResponse
    {
        $this->ensureCan('inquiry.convert');
        $this->assertVisible($inquiry);

        $inquiry->load(['branch:id,name', 'standard:id,name', 'followUps.creator:id,name']);

        return Ajax::success('OK', [
            'already_converted' => $inquiry->isConverted(),
            'converted_student_id' => $inquiry->converted_student_id,
            'inquiry' => (new InquiryResource($inquiry))->resolve(),
            'batches' => $this->studentService->batchesWithSubjects(
                (int) $inquiry->institute_id,
                $inquiry->branch_id ? (int) $inquiry->branch_id : null
            ),
        ]);
    }

    public function convert(Request $request, Inquiry $inquiry): JsonResponse
    {
        $this->ensureCan('inquiry.convert');
        $this->assertVisible($inquiry);

        if ($inquiry->isConverted()) {
            return Ajax::error('Inquiry already converted.', null, 422);
        }

        if ($limitError = $this->studentService->studentLimitError((int) $inquiry->institute_id)) {
            return Ajax::error($limitError, null, 403);
        }

        [$validated, $enrollments] = $this->validateConvert($request, $inquiry);

        try {
            $student = $this->studentService->convertFromInquiry($inquiry, $validated, $enrollments);
        } catch (RuntimeException $e) {
            return Ajax::error($e->getMessage(), null, 422);
        }

        $student->load(['branch:id,name', 'standard:id,name', 'parent', 'enrollments.batch', 'enrollments.subject']);

        return Ajax::success('Student created from inquiry.', (new StudentResource($student))->resolve(), 201);
    }

    public function receipt(Inquiry $inquiry): JsonResponse
    {
        $this->ensureCan('inquiry.receipt');
        $this->assertVisible($inquiry);

        $inquiry->load(['branch:id,name', 'standard:id,name', 'institute', 'followUps.creator:id,name']);

        return Ajax::success('OK', array_merge(
            (new InquiryResource($inquiry))->resolve(),
            ['pdf_url' => route('api.v1.inquiries.receipt-pdf', $inquiry)]
        ));
    }

    public function receiptPdf(Inquiry $inquiry): Response
    {
        $this->ensureCan('inquiry.receipt');
        $this->assertVisible($inquiry);

        $inquiry->load(['branch:id,name', 'standard:id,name', 'institute']);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $inquiry->inquiry_code ?: ('inquiry-'.$inquiry->id)) ?: 'inquiry-receipt';
        $filename = $safeName.'-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.inquiry_receipt', compact('inquiry'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    protected function validateConvert(Request $request, Inquiry $inquiry): array
    {
        $branchIds = BranchScope::for($this->actor())->allowedBranchIdsForInstitute((int) $inquiry->institute_id);

        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::in($branchIds ?: [0])],
            'form_fees' => ['nullable', 'numeric', 'min:0'],
            'form_fees_payment_method' => ['required', 'in:Cash,Online,UPI,Cheque'],
            'parent_password' => ['nullable', 'string', 'min:8'],
            'enrollments' => ['required', 'array', 'min:1'],
            'enrollments.*.batch_id' => ['required', 'integer', 'exists:batches,id'],
            'enrollments.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'enrollments.*.roll_no' => ['nullable', 'string', 'max:30'],
            'enrollments.*.total_fees' => ['required', 'numeric', 'min:0'],
            'enrollments.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        return [$validated, $validated['enrollments']];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateInquiry(Request $request, ?Inquiry $inquiry = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        $branchIds = BranchScope::for($this->actor())->allowedBranchIdsForInstitute($instituteId);
        $standardIds = Standard::query()->where('institute_id', $instituteId)->pluck('id')->all();

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_id' => ['nullable', 'integer', Rule::in($branchIds ?: [0])],
            'student_name' => ['required', 'string', 'max:150'],
            'father_name' => ['required', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'father_email' => ['required', 'email', 'max:150'],
            'father_phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'father_phone_2' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'dob' => ['nullable', 'date'],
            'gender' => ['required', 'in:Male,Female,Other,male,female,other'],
            'standard_id' => ['required', 'integer', Rule::in($standardIds ?: [0])],
            'aadhaar' => ['nullable', 'string', 'max:20'],
            'category' => ['required', 'in:General,OBC,SC,ST'],
            'inquiry_date' => ['required', 'date'],
            'school_name' => ['nullable', 'string', 'max:150'],
            'follow_up_date' => ['required', 'date', 'after_or_equal:inquiry_date'],
            'follow_up_remark' => ['required', 'string', 'max:2000'],
            'form_fees' => ['nullable', 'numeric', 'min:0'],
            'form_fees_payment_method' => ['nullable', 'in:Cash,Online,UPI,Cheque'],
            'address' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'source' => ['nullable', 'string', 'max:30'],
        ], [
            'follow_up_date.after_or_equal' => 'The follow-up date must be on or after the enquiry date.',
        ]);
    }

    protected function inquiryQuery()
    {
        $scope = BranchScope::for($this->actor());
        $query = Inquiry::query();
        if (! $scope->isRestricted()) {
            return $query;
        }

        $ids = $scope->branchIds();

        return $ids === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $ids);
    }

    protected function assertVisible(Inquiry $inquiry): void
    {
        if ((int) $inquiry->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Inquiry not found.');
        }

        if ($inquiry->branch_id) {
            BranchScope::for($this->actor())->assertCanAccessBranch((int) $inquiry->branch_id);
        }
    }
}
