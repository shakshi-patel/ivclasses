<?php

namespace App\Support\Traits;

use App\Models\Institute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToInstitute
{
    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function scopeForInstitute(Builder $query, ?int $instituteId = null): Builder
    {
        $id = $instituteId ?? (app()->bound('currentInstituteId') ? app('currentInstituteId') : null);

        if ($id) {
            $query->where($this->getTable().'.institute_id', $id);
        }

        return $query;
    }

    protected static function bootBelongsToInstitute(): void
    {
        static::addGlobalScope('institute', function (Builder $query) {
            $user = auth()->user();

            if (! $user || $user->isPlatformUser()) {
                return;
            }

            if ($user->institute_id) {
                $query->where($query->getModel()->getTable().'.institute_id', $user->institute_id);
            }
        });

        static::creating(function ($model) {
            if (! empty($model->institute_id)) {
                return;
            }

            $user = auth()->user();
            if ($user && $user->institute_id) {
                $model->institute_id = $user->institute_id;
            } elseif (app()->bound('currentInstituteId') && app('currentInstituteId')) {
                $model->institute_id = app('currentInstituteId');
            }
        });
    }
}
