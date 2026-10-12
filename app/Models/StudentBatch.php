<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBatch extends Model
{
    protected $table = 'student_batch';

    protected $fillable = [
        'student_id',
        'batch_id',
        'subject_id',
        'roll_no',
        'total_fees',
        'discount',
        'status',
        'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'total_fees' => 'decimal:2',
            'discount' => 'decimal:2',
            'enrolled_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
