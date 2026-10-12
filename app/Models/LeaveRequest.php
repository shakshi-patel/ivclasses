<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    public const TYPE_STAFF = 'staff';

    public const TYPE_STUDENT = 'student';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'institute_id',
        'branch_id',
        'requester_type',
        'requester_user_id',
        'student_id',
        'from_date',
        'to_date',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function totalDays(): int
    {
        if (! $this->from_date || ! $this->to_date) {
            return 0;
        }

        return $this->from_date->diffInDays($this->to_date) + 1;
    }

    public function statusPillClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'is-active',
            self::STATUS_REJECTED => 'is-navy',
            default => 'is-muted',
        };
    }
}
