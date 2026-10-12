<?php

namespace App\Http\Controllers\Admin;

use App\Models\Inquiry;
use App\Models\Institute;
use App\Models\Standard;
use App\Services\InquiryService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InquiryController extends AdminController
{
    public function __construct(
        protected InquiryService $inquiryService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('inquiry.view');

        $query = $this->inquiryQuery()
            ->with(['branch:id,name', 'standard:id,name']);

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

        $inquiries = $query->latest('inquiry_date')->latest('id')->get();
        $filters = $this->listFilterVars($request, ['branches', 'standards']);

        return view('admin.inquiry.inquiry', array_merge(compact('inquiries'), $filters));
    }

    public function create(): View
    {
        $this->ensureCan('inquiry.add');

        return view('admin.inquiry.add_inquiry', [
            'inquiry' => null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions(),
            'standards' => $this->standardOptions(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('inquiry.add');

        $validated = $this->validateInquiry($request);
        $validated['photo'] = $request->file('photo');
        $inquiry = $this->inquiryService->create($validated);

        if ($request->expectsJson()) {
            return Ajax::success('Inquiry created.', $inquiry, 201);
        }

        return redirect()->route('admin.inquiry')->with('success', 'Inquiry created.');
    }

    public function edit(Inquiry $inquiry): View
    {
        $this->ensureCan('inquiry.edit');
        $this->assertVisible($inquiry);

        return view('admin.inquiry.add_inquiry', [
            'inquiry' => $inquiry->load(['branch', 'standard', 'followUps']),
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($inquiry->institute_id),
            'standards' => $this->standardOptions($inquiry->institute_id),
        ]);
    }

    public function update(Request $request, Inquiry $inquiry): JsonResponse|RedirectResponse
    {
        $this->ensureCan('inquiry.edit');
        $this->assertVisible($inquiry);

        $validated = $this->validateInquiry($request, $inquiry);
        $validated['photo'] = $request->file('photo');
        $this->inquiryService->update($inquiry, $validated);

        if ($request->expectsJson()) {
            return Ajax::success('Inquiry updated.', $inquiry->fresh(['branch', 'standard', 'followUps']));
        }

        return redirect()->route('admin.inquiry')->with('success', 'Inquiry updated.');
    }

    public function destroy(Request $request, Inquiry $inquiry): JsonResponse|RedirectResponse
    {
        $this->ensureCan('inquiry.delete');
        $this->assertVisible($inquiry);

        $this->inquiryService->delete($inquiry);

        return $this->respondDestroyed($request, 'Inquiry deleted.', 'admin.inquiry');
    }

    public function show(Inquiry $inquiry): JsonResponse
    {
        $this->ensureCan('inquiry.show');
        $this->assertVisible($inquiry);

        $inquiry->load(['branch', 'standard', 'followUps.creator:id,name']);

        return Ajax::success('OK', $inquiry);
    }

    public function receipt(Request $request, Inquiry $inquiry): JsonResponse|View
    {
        $this->ensureCan('inquiry.receipt');
        $this->assertVisible($inquiry);

        $inquiry->load(['branch', 'standard', 'institute', 'followUps.creator:id,name']);

        if ($request->ajax() || $request->wantsJson()) {
            return Ajax::success('OK', [
                'title' => 'Inquiry Receipt — '.($inquiry->inquiry_code ?? ('#'.$inquiry->id)),
                'html' => view('admin.inquiry.partials.receipt_card', compact('inquiry'))->render(),
                'export_url' => route('admin.inquiry.receipt_pdf', $inquiry),
            ]);
        }

        return view('admin.print.inquiry_receipt', compact('inquiry'));
    }

    public function receiptPdf(Inquiry $inquiry): Response
    {
        $this->ensureCan('inquiry.receipt');
        $this->assertVisible($inquiry);

        $inquiry->load(['branch', 'standard', 'institute']);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $inquiry->inquiry_code ?: ('inquiry-'.$inquiry->id)) ?: 'inquiry-receipt';
        $filename = $safeName.'-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.inquiry_receipt', compact('inquiry'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function followUp(Request $request, Inquiry $inquiry): JsonResponse
    {
        $this->ensureCan('follow_up.add');
        $this->assertVisible($inquiry);

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:2000'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $followUp = $this->inquiryService->addFollowUp($inquiry, [
            'follow_up_at' => now(),
            'remark' => $validated['remark'],
            'next_follow_up_at' => $validated['next_follow_up_at'] ?? null,
        ]);

        return Ajax::success('Follow-up saved.', $followUp);
    }

    public function convertForm(Inquiry $inquiry): View|RedirectResponse
    {
        $this->ensureCan('inquiry.convert');
        $this->assertVisible($inquiry);

        if ($inquiry->isConverted()) {
            return redirect($this->studentLandingUrl($inquiry->converted_student_id))
                ->with('success', 'Inquiry already converted.');
        }

        $inquiry->load(['branch', 'standard', 'followUps']);

        return view('admin.inquiry.convert_student', [
            'inquiry' => $inquiry,
            'branches' => $this->branchOptions($inquiry->institute_id),
            'batches' => app(\App\Services\StudentService::class)
                ->batchesWithSubjects($inquiry->institute_id, $inquiry->branch_id),
        ]);
    }

    public function convert(Request $request, Inquiry $inquiry): JsonResponse|RedirectResponse
    {
        $this->ensureCan('inquiry.convert');
        $this->assertVisible($inquiry);

        if ($limitError = app(\App\Services\StudentService::class)->studentLimitError((int) $inquiry->institute_id)) {
            if ($request->expectsJson()) {
                return Ajax::error($limitError, null, 403);
            }

            return back()->withInput()->withErrors(['branch_id' => $limitError]);
        }

        [$validated, $enrollments] = $this->validateConvert($request, $inquiry);
        $student = app(\App\Services\StudentService::class)->convertFromInquiry($inquiry, $validated, $enrollments);

        if ($request->expectsJson()) {
            return Ajax::success('Student created from inquiry.', array_merge(
                $student->toArray(),
                ['redirect_url' => $this->studentLandingUrl($student->id)]
            ), 201);
        }

        return redirect($this->studentLandingUrl($student->id))
            ->with('success', 'Inquiry converted to student. Parent can log in with their email.');
    }

    protected function studentLandingUrl(int|string|null $studentId): string
    {
        $user = auth()->user();
        $canShow = $user && ($user->isPlatformUser() || $user->can('student.show'));

        if ($studentId && $canShow) {
            return route('admin.student.show', $studentId);
        }

        return $user && ($user->isPlatformUser() || $user->can('student.view'))
            ? route('admin.student')
            : route('admin.inquiry');
    }

    protected function inquiryQuery()
    {
        $scope = BranchScope::for();
        $query = Inquiry::query();

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
     * @return array<string, mixed>
     */
    protected function validateInquiry(Request $request, ?Inquiry $inquiry = null): array
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

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_id' => ['nullable', 'integer', Rule::in($branchIds)],
            'student_name' => ['required', 'string', 'max:150'],
            'father_name' => ['required', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'father_email' => ['required', 'email', 'max:150'],
            'father_phone' => ['required', 'string', 'max:15'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'dob' => ['nullable', 'date'],
            'gender' => ['required', 'in:Male,Female,Other,male,female,other'],
            'standard_id' => ['required', 'integer', Rule::in($standardIds)],
            'aadhaar' => ['nullable', 'string', 'max:20'],
            'category' => ['required', 'in:General,OBC,SC,ST'],
            'inquiry_date' => ['required', 'date'],
            'school_name' => ['nullable', 'string', 'max:150'],
            'follow_up_date' => ['required', 'date'],
            'follow_up_remark' => ['required', 'string', 'max:2000'],
            'form_fees' => ['nullable', 'numeric', 'min:0'],
            'form_fees_payment_method' => ['nullable', 'in:Cash,Online,UPI,Cheque'],
            'address' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'source' => ['nullable', 'string', 'max:30'],
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    protected function validateConvert(Request $request, Inquiry $inquiry): array
    {
        $branchIds = BranchScope::for()->allowedBranchIdsForInstitute((int) $inquiry->institute_id);

        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::in($branchIds)],
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

    protected function assertVisible(Inquiry $inquiry): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $inquiry->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($inquiry->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $inquiry->branch_id);
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
