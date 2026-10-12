<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IeCategory extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'type',
        'name',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(IncomeExpense::class, 'category_id');
    }
}
