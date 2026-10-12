<?php

namespace App\Models;

use App\Support\Traits\BelongsToInstitute;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Homework extends Model
{
    use BelongsToInstitute;
    use HasSoftDeleteWithUser;

    protected $table = 'homeworks';

    public const TYPE_TEXT = 'text';

    public const TYPE_FILE = 'file';

    public const TYPE_FILE_TEXT = 'file_text';

    public const TYPES = [
        self::TYPE_TEXT => 'Text',
        self::TYPE_FILE => 'File',
        self::TYPE_FILE_TEXT => 'File & Text',
    ];

    protected $fillable = [
        'institute_id',
        'branch_id',
        'batch_id',
        'subject_id',
        'teacher_user_id',
        'title',
        'type',
        'description',
        'homework_date',
        'due_date',
        'attachment_path',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'homework_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type ?? 'text');
    }

    public function attachmentUrl(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return asset('storage/'.$this->attachment_path);
    }
}
