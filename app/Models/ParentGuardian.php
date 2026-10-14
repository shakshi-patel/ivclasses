<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentGuardian extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'institute_id',
        'father_name',
        'mother_name',
        'email',
        'phone',
        'phone_2',
        'occupation',
        'address',
        'created_by',
        'updated_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
}
