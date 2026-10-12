<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Todo extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    protected $fillable = [
        'institute_id',
        'assigned_to_user_id',
        'title',
        'task_items',
        'due_date',
        'status',
        'remark',
        'reviewed_by',
        'reviewed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'task_items' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function statusPillClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'is-active',
            self::STATUS_REJECTED => 'is-navy',
            self::STATUS_COMPLETED => 'is-active',
            default => 'is-muted',
        };
    }

    public function isReviewableBy(User $user): bool
    {
        if ($user->isPlatformUser()) {
            return true;
        }

        if ((int) $this->assigned_to_user_id === (int) $user->id) {
            return true;
        }

        return $this->relationLoaded('assignees')
            ? $this->assignees->contains('id', $user->id)
            : $this->assignees()->where('users.id', $user->id)->exists();
    }

    /**
     * @return array<int, string>
     */
    public function taskLines(): array
    {
        $items = $this->task_items;
        if (is_array($items) && $items !== []) {
            return array_values(array_filter(array_map('strval', $items)));
        }

        return $this->title ? [$this->title] : [];
    }

    public function tasksDisplay(): string
    {
        return implode("\n", $this->taskLines());
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'todo_user')->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
