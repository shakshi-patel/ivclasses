<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryFollowUp extends Model
{
    protected $fillable = [
        'inquiry_id',
        'follow_up_at',
        'remark',
        'next_follow_up_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
