<?php

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

trait HasSoftDeleteWithUser
{
    use SoftDeletes;

    public static function bootHasSoftDeleteWithUser(): void
    {
        static::creating(function ($model) {
            if (auth()->check() && empty($model->created_by) && $model->isFillable('created_by')) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check() && $model->isFillable('updated_by')) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
