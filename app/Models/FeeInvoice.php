<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FeeInvoice extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'branch_id',
        'student_id',
        'student_batch_id',
        'title',
        'total_amount',
        'discount',
        'net_amount',
        'paid_amount',
        'due_amount',
        'status',
        'due_date',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentBatch::class, 'student_batch_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FeeFollowUp::class);
    }

    public function latestFollowUp(): HasOne
    {
        return $this->hasOne(FeeFollowUp::class)->latestOfMany('follow_up_at');
    }

    public function statusEnum(): \App\Enums\FeeInvoiceStatus
    {
        return \App\Enums\FeeInvoiceStatus::fromMixed($this->status);
    }

    public function isFollowUpPending(): bool
    {
        return $this->latestFollowUp?->status === 'pending';
    }

    /**
     * @return list<array{by: ?string, note: ?string, complete: ?string, next: ?string}>
     */
    public function followUpHistoryForUi(): array
    {
        return $this->followUps->map(fn ($f) => [
            'by' => $f->creator?->name,
            'note' => $f->remark,
            'complete' => $f->follow_up_at?->format('d M Y'),
            'next' => $f->next_follow_up_at?->format('d M Y'),
        ])->values()->all();
    }
}
