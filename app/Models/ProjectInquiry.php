<?php

namespace App\Models;

use App\Enums\DemoStatus;
use App\Enums\ProjectInquiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectInquiry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'inquiry_code',

        // Institute / Contact
        'institute_name',
        'contact_person_name',
        'email',
        'contact_no',
        'city',
        'state',
        'pincode',
        'address',

        // Institute information
        'student_count',
        'branch_count',

        // Inquiry
        'requirement',
        'message',
        'source',
        'status',

        // Demo
        'demo_status',
        'demo_date',
        'demo_start_time',
        'demo_end_time',
        'timezone',
        'demo_scheduled_at',
        'demo_completed_at',

        // Google Calendar / Meet
        'google_event_id',
        'google_meet_link',

        // Admin
        'assigned_to',
        'admin_notes',

        // Conversion
        'converted_at',
        'converted_class_id',
        'converted_institute_id',

        'deleted_at',
    ];

    protected $casts = [
        'demo_date' => 'date',
        'demo_scheduled_at' => 'datetime',
        'demo_completed_at' => 'datetime',
        'converted_at' => 'datetime',
        'student_count' => 'integer',
        'branch_count' => 'integer',
        'status' => ProjectInquiryStatus::class,
        'demo_status' => DemoStatus::class,
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedInstitute(): BelongsTo
    {
        return $this->belongsTo(Institute::class, 'converted_institute_id');
    }

    public function isConverted(): bool
    {
        return $this->converted_institute_id !== null || $this->converted_at !== null;
    }
}
