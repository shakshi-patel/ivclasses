<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteOnboardingSkip extends Model
{
    protected $fillable = [
        'institute_id',
        'module_help_step_id',
    ];
}
