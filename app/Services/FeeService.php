<?php

namespace App\Services;

use App\Models\FeeFollowUp;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\StudentBatch;
use Illuminate\Support\Facades\DB;

class FeeService
{
    public function __construct(
        protected IncomeExpenseService $incomeExpenseService
    ) {
    }
    /**
     * Aggregated fee profile for student view (no invoice auto-create).
     *
     * @return array{
     *     total: float,
     *     paid: float,
     *     due: float,
     *     payments: \Illuminate\Support\Collection<int, FeePayment>,
     *     followUps: \Illuminate\Support\Collection<int, FeeFollowUp>
     * }
     */
    public function profileForStudent(Student $student): array
    {
        $student->loadMissing(['enrollments']);

        $invoices = FeeInvoice::query()
            ->where('student_id', $student->id)
            ->get();

        if ($invoices->isNotEmpty()) {
            $total = round((float) $invoices->sum('net_amount'), 2);
            $paid = round((float) $invoices->sum('paid_amount'), 2);
            $due = round((float) $invoices->sum('due_amount'), 2);
        } else {
            $total = round((float) $student->enrollments->sum(
                fn(StudentBatch $e) => max(0, (float) $e->total_fees - (float) ($e->discount ?? 0))
            ), 2);
            $paid = round((float) FeePayment::query()->where('student_id', $student->id)->sum('amount'), 2);
            $due = round(max(0, $total - $paid), 2);
        }

        $payments = FeePayment::query()
            ->where('student_id', $student->id)
            ->with(['collector:id,name', 'invoice:id,title'])
            ->latest('payment_date')
            ->latest('id')
            ->get();

        $followUps = FeeFollowUp::query()
            ->whereHas('invoice', fn($q) => $q->where('student_id', $student->id))
            ->with('creator:id,name')
            ->latest('follow_up_at')
            ->latest('id')
            ->get();

        return [
            'total' => $total,
            'paid' => $paid,
            'due' => $due,
            'payments' => $payments,
            'followUps' => $followUps,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function studentSummary(Student $student, int $batchId): array
    {
        $enrollment = $this->findEnrollment($student, $batchId);
        abort_unless($enrollment, 404, 'Student is not enrolled in this batch.');

        $invoice = $this->resolveOrCreateInvoice($student, $enrollment);
        $invoice->load([
            'payments' => fn($q) => $q->with('collector:id,name')->latest('payment_date')->latest('id'),
        ]);

        $student->load(['parent.user', 'standard:id,name']);

        $fatherName = $student->parent?->father_name
            ?: $student->parent?->user?->name;

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'admission_no' => $student->admission_no,
                'father_name' => $fatherName,
                'father_email' => $student->parent?->email ?: $student->parent?->user?->email,
                'father_phone' => $student->parent?->phone ?: $student->parent?->user?->phone,
                'standard' => $student->standard?->name,
            ],
            'invoice' => [
                'id' => $invoice->id,
                'title' => $invoice->title,
                'total_amount' => (float) $invoice->total_amount,
                'discount' => (float) $invoice->discount,
                'net_amount' => (float) $invoice->net_amount,
                'paid_amount' => (float) $invoice->paid_amount,
                'due_amount' => (float) $invoice->due_amount,
                'status' => $invoice->status,
            ],
            'enrollment' => [
                'id' => $enrollment->id,
                'batch_id' => $enrollment->batch_id,
                'batch_name' => $enrollment->batch?->name,
            ],
            'payments' => $invoice->payments->map(fn(FeePayment $p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'payment_date' => $p->payment_date?->format('d M Y'),
                'payment_method' => $p->payment_method,
                'receipt_no' => $p->receipt_no,
                'collector' => $p->collector?->name,
                'note' => $p->note,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function collect(array $data): FeePayment
    {
        return DB::transaction(function () use ($data) {
            $student = Student::query()->findOrFail((int) $data['student_id']);
            $enrollment = $this->findEnrollment($student, (int) $data['batch_id']);
            abort_unless($enrollment, 422, 'Student is not enrolled in this batch.');

            $invoice = $this->resolveOrCreateInvoice($student, $enrollment);
            $amount = round((float) $data['amount'], 2);

            if ($amount > (float) $invoice->due_amount) {
                throw new \InvalidArgumentException('Received amount cannot exceed pending amount.');
            }

            $payment = FeePayment::query()->create([
                'fee_invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'receipt_no' => $this->nextReceiptNo((int) $student->institute_id),
                'transaction_ref' => $data['transaction_ref'] ?? null,
                'collected_by' => auth()->id(),
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->recalculateInvoice($invoice);

            $this->incomeExpenseService->createFromFeePayment($payment);

            if (!empty($data['note'])) {
                $this->addFollowUp($invoice, [
                    'remark' => $data['note'],
                    'next_follow_up_at' => null,
                ]);
            }

            return $payment->load(['invoice', 'student', 'collector']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addFollowUp(FeeInvoice $invoice, array $data): FeeFollowUp
    {
        return DB::transaction(function () use ($invoice, $data) {
            $nextAt = $data['next_follow_up_at'] ?? null;

            // "Complete" means nothing more to chase: no dues left AND no next follow-up
            // scheduled. A next date keeps it pending even if dues are cleared (e.g. a
            // reminder), and outstanding dues always keep it pending regardless of the date.
            $status = ((float) $invoice->due_amount > 0 || $nextAt) ? 'pending' : 'complete';

            $followUp = FeeFollowUp::query()->create([
                'fee_invoice_id' => $invoice->id,
                'follow_up_at' => $data['follow_up_at'] ?? now(),
                'next_follow_up_at' => $nextAt,
                'remark' => $data['remark'] ?? null,
                'status' => $status,
                'created_by' => auth()->id(),
            ]);

            return $followUp->load('creator:id,name');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function followUpOnly(array $data): FeeFollowUp
    {
        return DB::transaction(function () use ($data) {
            $student = Student::query()->findOrFail((int) $data['student_id']);
            $enrollment = $this->findEnrollment($student, (int) $data['batch_id']);
            abort_unless($enrollment, 422, 'Student is not enrolled in this batch.');

            $invoice = $this->resolveOrCreateInvoice($student, $enrollment);

            return $this->addFollowUp($invoice, [
                'remark' => $data['remark'],
                'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
            ]);
        });
    }

    public function resolveOrCreateInvoice(Student $student, StudentBatch $enrollment): FeeInvoice
    {
        $existing = FeeInvoice::query()
            ->where('student_id', $student->id)
            ->where('student_batch_id', $enrollment->id)
            ->first();

        if ($existing) {
            $this->syncInvoiceFromEnrollment($existing, $enrollment);

            return $existing->fresh();
        }

        $total = (float) $enrollment->total_fees;
        $discount = (float) ($enrollment->discount ?? 0);
        $net = max(0, $total - $discount);

        $invoice = FeeInvoice::query()->create([
            'institute_id' => $student->institute_id,
            'branch_id' => $student->branch_id,
            'student_id' => $student->id,
            'student_batch_id' => $enrollment->id,
            'title' => $enrollment->batch?->name ? $enrollment->batch->name . ' Fees' : 'Batch Fees',
            'total_amount' => $total,
            'discount' => $discount,
            'net_amount' => $net,
            'paid_amount' => 0,
            'due_amount' => $net,
            'status' => $net > 0 ? 'unpaid' : 'paid',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return $invoice;
    }

    protected function syncInvoiceFromEnrollment(FeeInvoice $invoice, StudentBatch $enrollment): void
    {
        $total = (float) $enrollment->total_fees;
        $discount = (float) ($enrollment->discount ?? 0);
        $net = max(0, $total - $discount);

        $invoice->update([
            'total_amount' => $total,
            'discount' => $discount,
            'net_amount' => $net,
            'title' => $enrollment->batch?->name ? $enrollment->batch->name . ' Fees' : $invoice->title,
            'updated_by' => auth()->id(),
        ]);

        $this->recalculateInvoice($invoice);
    }

    public function recalculateInvoice(FeeInvoice $invoice): void
    {
        $paid = (float) $invoice->payments()->sum('amount');
        $net = (float) $invoice->net_amount;
        $due = max(0, round($net - $paid, 2));

        $status = 'unpaid';
        if ($paid > 0 && $due > 0) {
            $status = 'partial';
        } elseif ($due <= 0) {
            $status = 'paid';
        }

        $invoice->update([
            'paid_amount' => $paid,
            'due_amount' => $due,
            'status' => $status,
            'updated_by' => auth()->id(),
        ]);
    }

    public function deletePayment(FeePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            $payment->delete();
            if ($invoice) {
                $this->recalculateInvoice($invoice->fresh());
            }
        });
    }

    protected function findEnrollment(Student $student, int $batchId): ?StudentBatch
    {
        return StudentBatch::query()
            ->with('batch:id,name')
            ->where('student_id', $student->id)
            ->where('batch_id', $batchId)
            ->where('status', 'active')
            ->first();
    }

    protected function nextReceiptNo(int $instituteId): string
    {
        return \App\Support\SequenceGenerator::forInstitute($instituteId, function () use ($instituteId) {
            $count = FeePayment::query()
                ->whereHas('invoice', fn($q) => $q->where('institute_id', $instituteId))
                ->whereYear('created_at', now()->year)
                ->count() + 1;

            return 'RCP-' . str_pad((string) $instituteId, 3, '0', STR_PAD_LEFT)
                . '-' . now()->format('Y') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        });
    }

}
