<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Teacher extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'user_id',
        'institute_id',
        'employee_code',
        'specialization',
        'address',
        'expected_login_time',
        'grace_minutes',
        'joining_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'grace_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user', 'user_id', 'branch_id', 'user_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryBranch(): ?Branch
    {
        return $this->branches()->wherePivot('is_primary', true)->first()
            ?? $this->branches()->first();
    }

    public function assignedBatches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'batch_teacher', 'teacher_user_id', 'batch_id', 'user_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->user?->is_active;
    }
}
