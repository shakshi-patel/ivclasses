<?php

namespace App\Services;

use App\Models\FeePayment;
use App\Models\GstInvoice;
use App\Models\Institute;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GstInvoiceService
{
    /**
     * @return Collection<int, GstInvoice>
     */
    public function listForStudent(Student $student): Collection
    {
        return GstInvoice::query()
            ->where('student_id', $student->id)
            ->with(['payment:id,receipt_no,amount,payment_date', 'institute:id,name,address,gstin', 'branch:id,name'])
            ->latest('id')
            ->get();
    }

    public function generateFromPayment(FeePayment $payment): GstInvoice
    {
        return DB::transaction(function () use ($payment) {
            $existing = GstInvoice::query()->where('fee_payment_id', $payment->id)->first();
            if ($existing) {
                return $existing;
            }

            $payment->load(['student.parent', 'student.branch', 'invoice']);
            $student = $payment->student;
            $instituteId = (int) $student->institute_id;
            $institute = Institute::query()->findOrFail($instituteId);

            $taxable = round((float) $payment->amount, 2);
            $cgst = round($taxable * 0.09, 2);
            $sgst = round($taxable * 0.09, 2);
            $total = round($taxable + $cgst + $sgst, 2);

            return GstInvoice::query()->create([
                'institute_id' => $instituteId,
                'branch_id' => $student->branch_id,
                'fee_payment_id' => $payment->id,
                'student_id' => $student->id,
                'gstin' => $institute->gstin,
                'invoice_no' => $this->nextInvoiceNo($instituteId),
                'taxable' => $taxable,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => 0,
                'total' => $total,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    protected function nextInvoiceNo(int $instituteId): string
    {
        $count = GstInvoice::withTrashed()
            ->where('institute_id', $instituteId)
            ->whereYear('created_at', now()->year)
            ->count() + 1;

        return 'GST-'.str_pad((string) $instituteId, 3, '0', STR_PAD_LEFT)
            .'-'.now()->format('Y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
