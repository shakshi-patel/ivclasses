<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAsset extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    public const STATUS_WORKING = 'working';

    public const STATUS_REPAIR = 'repair';

    public const STATUS_DAMAGED = 'damaged';

    public const STATUSES = [
        self::STATUS_WORKING => 'Working',
        self::STATUS_REPAIR => 'Repair',
        self::STATUS_DAMAGED => 'Damaged',
    ];

    protected $fillable = [
        'institute_id',
        'branch_id',
        'category_id',
        'name',
        'quantity',
        'purchase_date',
        'purchase_amount',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'purchase_amount' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusPillClass(): string
    {
        return match ($this->status) {
            self::STATUS_WORKING => 'is-active',
            self::STATUS_REPAIR => 'is-navy',
            default => 'is-muted',
        };
    }
}
