<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notice extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_PARENTS = 'parents';

    public const AUDIENCE_STUDENTS = 'students';

    public const AUDIENCE_TEACHERS = 'teachers';

    public const AUDIENCES = [
        self::AUDIENCE_ALL => 'All',
        self::AUDIENCE_PARENTS => 'Parents',
        self::AUDIENCE_STUDENTS => 'Students',
        self::AUDIENCE_TEACHERS => 'Teachers',
    ];

    protected $fillable = [
        'institute_id',
        'title',
        'body',
        'notice_date',
        'audience',
        'status',
        'image_path',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'notice_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'notice_branch');
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'notice_batch');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset('storage/'.$this->image_path);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function branchesLabel(): string
    {
        if ($this->relationLoaded('branches')) {
            return $this->branches->isEmpty()
                ? 'All branches'
                : $this->branches->pluck('name')->implode(', ');
        }

        return 'All branches';
    }

    public function batchesLabel(): string
    {
        if ($this->relationLoaded('batches')) {
            return $this->batches->isEmpty()
                ? 'All batches'
                : $this->batches->pluck('name')->implode(', ');
        }

        return 'All batches';
    }

    public function scopeLabel(): string
    {
        $parts = [$this->branchesLabel()];
        if ($this->relationLoaded('batches') && $this->batches->isNotEmpty()) {
            $parts[] = $this->batchesLabel();
        }

        return implode(' · ', $parts);
    }
}
