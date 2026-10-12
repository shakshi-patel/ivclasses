<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstInvoice extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'branch_id',
        'fee_payment_id',
        'student_id',
        'gstin',
        'invoice_no',
        'taxable',
        'cgst',
        'sgst',
        'igst',
        'total',
        'pdf_path',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'taxable' => 'decimal:2',
            'cgst' => 'decimal:2',
            'sgst' => 'decimal:2',
            'igst' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'fee_payment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }
}
