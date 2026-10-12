<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'user_id',
        'institute_id',
        'parent_id',
        'branch_id',
        'admission_no',
        'name',
        'dob',
        'gender',
        'photo_path',
        'medical_notes',
        'aadhaar',
        'category',
        'admission_date',
        'school_name',
        'standard_id',
        'form_fees',
        'form_fees_payment_method',
        'address',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'admission_date' => 'date',
            'form_fees' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentBatch::class);
    }

    public function feeInvoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
