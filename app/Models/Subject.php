<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'name',
        'code',
        'status',
        'created_by',
        'updated_by',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
