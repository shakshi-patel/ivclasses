<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModuleHelp extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'module_key',
        'title',
        'intro',
        'sort_order',
        'status',
        'is_onboarding',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_onboarding' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ModuleHelpStep::class)->orderBy('sort_order')->orderBy('id');
    }

    public function roleRows(): HasMany
    {
        return $this->hasMany(ModuleHelpRole::class);
    }

    /**
     * @return list<string>
     */
    public function roleNames(): array
    {
        if ($this->relationLoaded('roleRows')) {
            return $this->roleRows->pluck('role_name')->filter()->values()->all();
        }

        return $this->roleRows()->pluck('role_name')->filter()->values()->all();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function moduleLabel(): string
    {
        $catalog = \App\Support\PlanModules::crmCatalog();

        return $catalog[$this->module_key] ?? ucwords(str_replace('_', ' ', $this->module_key));
    }

    /**
     * Payload for admin preview modal / future institute wizard.
     *
     * @return array<string, mixed>
     */
    public function toPreviewArray(): array
    {
        $this->loadMissing(['steps', 'roleRows']);

        return [
            'id' => $this->id,
            'module_key' => $this->module_key,
            'module_label' => $this->moduleLabel(),
            'title' => $this->title,
            'intro' => $this->intro,
            'roles' => $this->roleNames(),
            'steps_count' => $this->steps->count(),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'steps' => $this->steps->map(fn (ModuleHelpStep $step) => $step->toPreviewArray())->values()->all(),
        ];
    }
}
