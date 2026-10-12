<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'branch_id',
        'standard_id',
        'name',
        'start_time',
        'end_time',
        'capacity',
        'academic_year',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
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

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'batch_subject')
            ->withPivot(['teacher_user_id', 'fees_amount'])
            ->withTimestamps();
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'batch_teacher', 'batch_id', 'teacher_user_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentBatch::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class);
    }

    public function primaryTeacher(): ?User
    {
        return $this->teachers()->wherePivot('is_primary', true)->first()
            ?? $this->teachers()->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function timingLabel(): string
    {
        if (! $this->start_time && ! $this->end_time) {
            return '—';
        }

        $start = $this->start_time ? substr((string) $this->start_time, 0, 5) : '—';
        $end = $this->end_time ? substr((string) $this->end_time, 0, 5) : '—';

        return $start.' – '.$end;
    }
}
