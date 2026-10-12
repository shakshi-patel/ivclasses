<?php

namespace App\Services;

use App\Models\FeePayment;
use App\Models\IeCategory;
use App\Models\IncomeExpense;
use App\Models\Inquiry;
use Illuminate\Support\Facades\DB;

class IncomeExpenseService
{
    /**
     * @return array{income: float, expense: float, balance: float}
     */
    public function totals(int $instituteId, ?int $branchId = null): array
    {
        $query = IncomeExpense::query()->where('institute_id', $instituteId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $income = (float) (clone $query)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function createFromFeePayment(FeePayment $payment): IncomeExpense
    {
        $existing = IncomeExpense::query()->where('fee_payment_id', $payment->id)->first();
        if ($existing) {
            return $existing;
        }

        $payment->loadMissing(['student:id,name,institute_id,branch_id', 'invoice:id,title']);

        $student = $payment->student;
        abort_unless($student, 422, 'Fee payment student not found.');

        $instituteId = (int) $student->institute_id;
        $this->ensureDefaultCategories($instituteId);

        $category = $this->educationFeeCategory($instituteId);
        $receipt = $payment->receipt_no ? ' ('.$payment->receipt_no.')' : '';

        return IncomeExpense::query()->create([
            'institute_id' => $instituteId,
            'branch_id' => $student->branch_id,
            'category_id' => $category->id,
            'fee_payment_id' => $payment->id,
            'type' => 'income',
            'title' => 'Fees - '.$student->name.$receipt,
            'amount' => $payment->amount,
            'entry_date' => $payment->payment_date,
            'payment_method' => $payment->payment_method,
            'note' => $payment->note ?: ($payment->transaction_ref ? 'Ref: '.$payment->transaction_ref : null),
            'created_by' => $payment->created_by ?? auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Sync inquiry form fee into Income & Expense (Form Fee category).
     * Creates / updates when amount > 0; removes when amount is 0.
     */
    public function syncFromInquiryFormFee(Inquiry $inquiry): ?IncomeExpense
    {
        $existing = IncomeExpense::query()
            ->withTrashed()
            ->where('inquiry_id', $inquiry->id)
            ->first();

        $amount = (float) ($inquiry->form_fees ?? 0);

        if ($amount <= 0) {
            if ($existing && ! $existing->trashed()) {
                $existing->delete();
            }

            return null;
        }

        $instituteId = (int) $inquiry->institute_id;
        $this->ensureDefaultCategories($instituteId);
        $category = $this->formFeeCategory($instituteId);

        $payload = [
            'institute_id' => $instituteId,
            'branch_id' => $inquiry->branch_id,
            'category_id' => $category->id,
            'inquiry_id' => $inquiry->id,
            'type' => 'income',
            'title' => 'Form Fee - '.$inquiry->student_name.' ('.$inquiry->inquiry_code.')',
            'amount' => $amount,
            'entry_date' => $inquiry->inquiry_date?->toDateString() ?? now()->toDateString(),
            'payment_method' => $inquiry->form_fees_payment_method ?: 'Cash',
            'note' => 'Inquiry form fee',
            'updated_by' => auth()->id(),
        ];

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->update($payload);

            return $existing->fresh();
        }

        return IncomeExpense::query()->create([
            ...$payload,
            'created_by' => auth()->id(),
        ]);
    }

    public function formFeeCategory(int $instituteId): IeCategory
    {
        $this->ensureDefaultCategories($instituteId);

        return IeCategory::query()
            ->where('institute_id', $instituteId)
            ->where('type', 'income')
            ->where('name', 'Form Fee')
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): IncomeExpense
    {
        return DB::transaction(function () use ($data) {
            $instituteId = (int) $data['institute_id'];
            $this->ensureDefaultCategories($instituteId);

            $category = IeCategory::query()->findOrFail((int) $data['category_id']);

            return IncomeExpense::query()->create([
                'institute_id' => $instituteId,
                'branch_id' => $data['branch_id'] ?? null,
                'category_id' => $category->id,
                'type' => $category->type,
                'title' => $data['title'] ?? $category->name,
                'amount' => $data['amount'],
                'entry_date' => $data['entry_date'],
                'payment_method' => $data['payment_method'] ?? 'Cash',
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    public function delete(IncomeExpense $entry): void
    {
        $entry->delete();
    }

    public function educationFeeCategory(int $instituteId): IeCategory
    {
        $this->ensureDefaultCategories($instituteId);

        return IeCategory::query()
            ->where('institute_id', $instituteId)
            ->where('type', 'income')
            ->where('name', 'Education Fee')
            ->firstOrFail();
    }

    public function ensureDefaultCategories(int $instituteId): void
    {
        $defaults = [
            'income' => ['Education Fee', 'Form Fee', 'Admission Fee', 'Other Income'],
            'expense' => ['Office Expense', 'Salary', 'Electricity', 'Rent', 'Other Expense'],
        ];

        foreach ($defaults as $type => $names) {
            foreach ($names as $index => $name) {
                IeCategory::query()->firstOrCreate(
                    ['institute_id' => $instituteId, 'type' => $type, 'name' => $name],
                    ['sort_order' => $index + 1, 'status' => 'active', 'created_by' => auth()->id()]
                );
            }
        }
    }
}
