<?php

namespace App\Models;

use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeFollowUp extends Model
{
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'fee_invoice_id',
        'follow_up_at',
        'next_follow_up_at',
        'remark',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
