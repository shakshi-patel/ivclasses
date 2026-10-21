<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\FeePaymentResource;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Services\FeeService;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Support\ParentBatchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FeeController extends ApiController
{
    public function __construct(
        protected FeeService $feeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($this->actor()->isParent()) {
            $this->ensureCanAny(['fees.view', 'parent_dashboard.list_fees', 'parent_dashboard.kpi_fees']);
        } else {
            $this->ensureCan('fees.view');
        }

        $query = $this->paymentQuery()
            ->with([
                'student:id,name,admission_no,institute_id,branch_id,standard_id',
                'student.branch:id,name',
                'student.standard:id,name',
                'invoice:id,title,status,student_batch_id',
                'invoice.enrollment.batch:id,name',
                'collector:id,name',
            ]);

        if ($request->filled('branch_id')) {
            $query->whereHas('student', fn ($q) => $q->where('branch_id', (int) $request->input('branch_id')));
        }
        if ($request->filled('batch_id')) {
            $query->whereHas('invoice.enrollment', fn ($q) => $q->where('batch_id', (int) $request->input('batch_id')));
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->input('student_id'));
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->input('end_date'));
        }

        return $this->paginated(
            'OK',
            $query->latest('payment_date')->latest('id')->paginate($this->perPage())
                ->through(fn (FeePayment $payment) => (new FeePaymentResource($payment))->resolve($request))
        );
    }

    public function studentSummary(Request $request): JsonResponse
    {
        if ($this->actor()->isParent()) {
            $this->ensureCanAny([
                'fees.show',
                'fees.view',
                'parent_dashboard.kpi_fees',
                'parent_dashboard.list_fees',
            ]);
        } else {
            $this->ensureCanAny(['fees.show', 'fees.view']);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);
        $this->assertStudentVisible($student);

        $profile = $this->feeService->profileForStudent($student);
        $invoices = FeeInvoice::query()
            ->where('student_id', $student->id)
            ->with(['enrollment.batch:id,name'])
            ->when(
                ! empty($validated['batch_id']),
                fn ($q) => $q->whereHas('enrollment', fn ($eq) => $eq->where('batch_id', (int) $validated['batch_id']))
            )
            ->orderByDesc('id')
            ->get();

        $payments = $profile['payments'];
        if (! empty($validated['batch_id'])) {
            $invoiceIds = $invoices->pluck('id')->all();
            $payments = $payments->whereIn('fee_invoice_id', $invoiceIds)->values();
        }

        return Ajax::success('OK', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'admission_no' => $student->admission_no,
            ],
            'totals' => [
                'total' => $profile['total'],
                'paid' => $profile['paid'],
                'due' => $profile['due'],
            ],
            'invoices' => $invoices->map(fn (FeeInvoice $invoice) => [
                'id' => $invoice->id,
                'title' => $invoice->title,
                'total_amount' => (float) $invoice->total_amount,
                'discount' => (float) $invoice->discount,
                'net_amount' => (float) $invoice->net_amount,
                'paid_amount' => (float) $invoice->paid_amount,
                'due_amount' => (float) $invoice->due_amount,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toDateString(),
                'batch' => $invoice->enrollment?->batch ? [
                    'id' => $invoice->enrollment->batch->id,
                    'name' => $invoice->enrollment->batch->name,
                ] : null,
            ])->values()->all(),
            'payments' => $payments->map(fn (FeePayment $payment) => [
                'id' => $payment->id,
                'receipt_no' => $payment->receipt_no,
                'amount' => (float) $payment->amount,
                'payment_date' => $payment->payment_date?->toDateString(),
                'payment_method' => $payment->payment_method,
                'collector' => $payment->collector?->name,
                'note' => $payment->note,
            ])->values()->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('fees.add');

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:30'],
            'transaction_ref' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);
        $this->assertStudentVisible($student);

        try {
            $payment = $this->feeService->collect($validated);
        } catch (\InvalidArgumentException $e) {
            return Ajax::error($e->getMessage(), ['amount' => [$e->getMessage()]], 422);
        }

        $payment->load([
            'student:id,name,admission_no,institute_id,branch_id,standard_id',
            'student.branch:id,name',
            'student.standard:id,name',
            'invoice:id,title,status,student_batch_id',
            'invoice.enrollment.batch:id,name',
            'collector:id,name',
        ]);

        return Ajax::success('Payment recorded.', (new FeePaymentResource($payment))->resolve($request), 201);
    }

    public function destroy(FeePayment $payment): JsonResponse
    {
        $this->ensureCan('fees.delete');
        $payment->loadMissing('student');
        $this->assertStudentVisible($payment->student);
        $this->feeService->deletePayment($payment);

        return Ajax::success('Fee payment deleted.');
    }

    public function followUps(Request $request): JsonResponse
    {
        $this->ensureCan('fees_follow_up.view');

        $query = $this->invoiceQuery()
            ->with([
                'student:id,name,admission_no,branch_id,standard_id',
                'student.branch:id,name',
                'student.standard:id,name',
                'student.parent:id,father_name,phone',
                'enrollment.batch:id,name',
                'latestFollowUp.creator:id,name',
            ])
            ->where('due_amount', '>', 0);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('batch_id')) {
            $query->whereHas('enrollment', fn ($q) => $q->where('batch_id', (int) $request->input('batch_id')));
        }
        if ($request->filled('status') && $request->input('status') === 'pending') {
            $query->whereHas('latestFollowUp', fn ($q) => $q->where('status', 'pending'));
        }

        return $this->paginated(
            'OK',
            $query->orderByDesc('due_amount')->paginate($this->perPage())
                ->through(fn (FeeInvoice $invoice) => $this->mapDueInvoice($invoice))
        );
    }

    public function followUpStore(Request $request, FeeInvoice $invoice): JsonResponse
    {
        $this->ensureCanAny(['fees_follow_up.add', 'fees_follow_up.show']);
        $this->assertInvoiceVisible($invoice);

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:2000'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $followUp = $this->feeService->addFollowUp($invoice, [
            'remark' => $validated['remark'],
            'next_follow_up_at' => $validated['next_follow_up_at'] ?? null,
        ]);

        return Ajax::success('Follow-up saved.', [
            'id' => $followUp->id,
            'remark' => $followUp->remark,
            'status' => $followUp->status,
            'follow_up_at' => $followUp->follow_up_at?->toIso8601String(),
            'next_follow_up_at' => $followUp->next_follow_up_at?->toIso8601String(),
            'creator' => $followUp->creator ? [
                'id' => $followUp->creator->id,
                'name' => $followUp->creator->name,
            ] : null,
        ], 201);
    }

    public function followUpExportPdf(Request $request): Response
    {
        $this->ensureCan('fees_follow_up.export');

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer', 'exists:batches,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $query = $this->invoiceQuery()
            ->with([
                'student.parent',
                'student.standard:id,name',
                'branch:id,name',
                'enrollment.batch:id,name',
                'latestFollowUp.creator:id,name',
            ])
            ->where('due_amount', '>', 0);

        if (! empty($validated['branch_id'])) {
            $query->where('branch_id', (int) $validated['branch_id']);
        }
        if (! empty($validated['batch_ids'])) {
            $query->whereHas('enrollment', fn ($q) => $q->whereIn('batch_id', array_map('intval', $validated['batch_ids'])));
        }

        $invoices = $query->orderByDesc('due_amount')->get();
        $instituteName = $this->actor()->institute?->name ?? config('app.name');

        return Pdf::loadView('admin.print.pdf.fees_follow_up', [
            'invoices' => $invoices,
            'filters' => $validated,
            'instituteName' => $instituteName,
            'branchName' => null,
            'batchLabel' => null,
        ])->setPaper('a4', 'landscape')->download('fees-follow-up-'.now()->format('Ymd_His').'.pdf');
    }

    public function receipt(FeePayment $payment): JsonResponse
    {
        $this->ensureCan('fees.receipt');
        $this->loadReceipt($payment);
        $this->assertStudentVisible($payment->student);

        return Ajax::success('OK', array_merge(
            (new FeePaymentResource($payment))->resolve(),
            ['pdf_url' => route('api.v1.fees.receipt-pdf', $payment)]
        ));
    }

    public function receiptPdf(FeePayment $payment): Response
    {
        $this->ensureCan('fees.receipt');
        $this->loadReceipt($payment);
        $this->assertStudentVisible($payment->student);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $payment->receipt_no ?: ('fee-'.$payment->id)) ?: 'fee-receipt';
        $filename = $safeName.'-'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('admin.print.pdf.fee_receipt', compact('payment'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    protected function paymentQuery()
    {
        $query = FeePayment::query()
            ->whereHas('student', fn ($q) => $q->where('institute_id', $this->actor()->institute_id));

        $user = $this->actor();
        if ($user->isParent()) {
            $ids = ParentBatchScope::studentIds($user, $this->parentStudentId());

            return $ids->isEmpty()
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('student_id', $ids->all());
        }

        $scope = BranchScope::for($user);
        if ($scope->isRestricted()) {
            $ids = $scope->branchIds();
            $query->whereHas('student', fn ($q) => $ids === []
                ? $q->whereRaw('1 = 0')
                : $q->whereIn('branch_id', $ids));
        }

        return $query;
    }

    protected function loadReceipt(FeePayment $payment): void
    {
        $payment->load([
            'student.branch:id,name',
            'student.standard:id,name',
            'student.parent',
            'student.institute',
            'invoice.enrollment.batch:id,name',
            'collector:id,name',
        ]);
    }

    protected function assertStudentVisible(?Student $student): void
    {
        if (! $student) {
            abort(404, 'Student not found.');
        }

        if ((int) $student->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Student not found.');
        }

        $user = $this->actor();
        if ($user->isParent()) {
            ParentBatchScope::assertOwnsStudent($user, (int) $student->id);

            return;
        }

        if ($student->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $student->branch_id);
        }
    }

    protected function invoiceQuery()
    {
        $query = FeeInvoice::query()->where('institute_id', $this->actor()->institute_id);
        $scope = BranchScope::for($this->actor());
        if ($scope->isRestricted()) {
            $ids = $scope->branchIds();
            $query->whereIn('branch_id', $ids !== [] ? $ids : [0]);
        }

        return $query;
    }

    protected function assertInvoiceVisible(FeeInvoice $invoice): void
    {
        if ((int) $invoice->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Invoice not found.');
        }
        if ($invoice->branch_id) {
            BranchScope::for($this->actor())->assertCanAccessBranch((int) $invoice->branch_id);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapDueInvoice(FeeInvoice $invoice): array
    {
        $latest = $invoice->latestFollowUp;

        return [
            'id' => $invoice->id,
            'title' => $invoice->title,
            'due_amount' => (float) $invoice->due_amount,
            'net_amount' => (float) $invoice->net_amount,
            'paid_amount' => (float) $invoice->paid_amount,
            'due_date' => $invoice->due_date?->toDateString(),
            'status' => $invoice->status,
            'student' => $invoice->student ? [
                'id' => $invoice->student->id,
                'name' => $invoice->student->name,
                'admission_no' => $invoice->student->admission_no,
                'phone' => $invoice->student->parent?->phone,
                'father_name' => $invoice->student->parent?->father_name,
            ] : null,
            'batch' => $invoice->enrollment?->batch ? [
                'id' => $invoice->enrollment->batch->id,
                'name' => $invoice->enrollment->batch->name,
            ] : null,
            'latest_follow_up' => $latest ? [
                'id' => $latest->id,
                'remark' => $latest->remark,
                'status' => $latest->status,
                'next_follow_up_at' => $latest->next_follow_up_at?->toIso8601String(),
                'creator' => $latest->creator ? [
                    'id' => $latest->creator->id,
                    'name' => $latest->creator->name,
                ] : null,
            ] : null,
        ];
    }
}
