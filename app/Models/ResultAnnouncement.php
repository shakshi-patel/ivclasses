<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ResultAnnouncement extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'institute_id',
        'exam_id',
        'title',
        'body',
        'result_date',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'result_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function results(): HasManyThrough
    {
        return $this->hasManyThrough(ExamResult::class, Exam::class, 'id', 'exam_id', 'exam_id', 'id');
    }
}
