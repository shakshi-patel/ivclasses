<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Standard extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'name',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
