<?php

namespace App\Models;

use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FeePayment extends Model
{
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'fee_invoice_id',
        'student_id',
        'amount',
        'payment_date',
        'payment_method',
        'receipt_no',
        'transaction_ref',
        'collected_by',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('institute', function (Builder $query) {
            $user = auth()->user();

            if (! $user || $user->isPlatformUser() || ! $user->institute_id) {
                return;
            }

            $query->whereHas('student', function (Builder $studentQuery) use ($user) {
                $studentQuery->where('institute_id', $user->institute_id);
            });
        });

        static::deleted(function (FeePayment $payment) {
            $payment->incomeExpense?->delete();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function gstInvoice(): HasOne
    {
        return $this->hasOne(GstInvoice::class, 'fee_payment_id');
    }

    public function incomeExpense(): HasOne
    {
        return $this->hasOne(IncomeExpense::class, 'fee_payment_id');
    }

    public function invoiceStatus(): \App\Enums\FeeInvoiceStatus
    {
        return \App\Enums\FeeInvoiceStatus::fromMixed($this->invoice?->status ?? 'paid');
    }

    public function standardDisplayName(): string
    {
        return $this->student?->standard?->name
            ?? $this->invoice?->enrollment?->batch?->standard?->name
            ?? '—';
    }
}
