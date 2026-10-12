<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoAvailability extends Model
{
    protected $table = 'demo_availability';

    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'buffer_minutes',
        'is_active',
    ];
}
