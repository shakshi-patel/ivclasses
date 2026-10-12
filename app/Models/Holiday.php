<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Holiday extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'title',
        'start_date',
        'end_date',
        'reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'holiday_branch');
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'holiday_batch');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dateLabel(): string
    {
        $start = $this->start_date?->format('d M Y');
        $end = $this->end_date?->format('d M Y');

        if ($start === $end) {
            return $start ?? '—';
        }

        return ($start ?? '—').' To '.($end ?? '—');
    }

    public function branchesLabel(): string
    {
        if ($this->relationLoaded('branches')) {
            return $this->branches->isEmpty()
                ? 'All Branches'
                : $this->branches->pluck('name')->implode(', ');
        }

        return 'All Branches';
    }

    public function batchesLabel(): string
    {
        if ($this->relationLoaded('batches')) {
            return $this->batches->isEmpty()
                ? 'All Batches'
                : $this->batches->pluck('name')->implode(', ');
        }

        return 'All Batches';
    }
}
