<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'branch_id',
        'inquiry_code',
        'student_name',
        'father_name',
        'mother_name',
        'parent_name',
        'phone',
        'phone_2',
        'email',
        'dob',
        'gender',
        'aadhaar',
        'category',
        'inquiry_date',
        'school_name',
        'standard_id',
        'interested_subject',
        'source',
        'status',
        'assigned_to',
        'converted_student_id',
        'form_fees',
        'form_fees_payment_method',
        'photo_path',
        'address',
        'father_occupation',
        'next_follow_up_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'inquiry_date' => 'date',
            'form_fees' => 'decimal:2',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(InquiryFollowUp::class)->latest('follow_up_at');
    }

    public function latestFollowUp(): ?InquiryFollowUp
    {
        return $this->followUps()->first();
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function displayParentName(): string
    {
        return $this->father_name ?: $this->parent_name ?: '—';
    }

    public function isFollowUpPending(): bool
    {
        return $this->next_follow_up_at !== null;
    }

    /**
     * History rows for follow-up modal / data-history JSON.
     *
     * @return list<array{by: string, note: string, complete: string, next: string}>
     */
    public function followUpHistoryForUi(): array
    {
        return $this->followUps->map(fn ($f) => [
            'by' => $f->creator?->name ?? '—',
            'note' => $f->remark ?? '—',
            'complete' => optional($f->follow_up_at)->format('d M Y') ?? '—',
            'next' => optional($f->next_follow_up_at)->format('d M Y') ?? '—',
        ])->values()->all();
    }
}
