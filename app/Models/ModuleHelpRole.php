<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleHelpRole extends Model
{
    protected $table = 'module_help_role';

    protected $fillable = [
        'module_help_id',
        'role_name',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(ModuleHelp::class, 'module_help_id');
    }
}
