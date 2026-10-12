<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(InventoryAsset::class, 'category_id');
    }
}
