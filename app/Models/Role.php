<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'guard_name',
        'team_id',
        'parent_role_id',
        'image_path',
        'is_system',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function parentRole(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_role_id');
    }

    public function assignableRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'role_assignable',
            'role_id',
            'assignable_role_id'
        )->withTimestamps();
    }

    public function avatarUrl(): string
    {
        if ($this->image_path) {
            return asset('storage/'.$this->image_path);
        }

        $slug = strtolower(str_replace([' ', '-'], '_', (string) $this->name));
        $map = [
            'developer' => 'developer',
            'platform_admin' => 'developer',
            'institute_admin' => 'institute_admin',
            'admin' => 'institute_admin',
            'principal' => 'principal',
            'accountant' => 'accountant',
            'receptionist' => 'receptionist',
            'branch_manager' => 'receptionist',
            'teacher' => 'teacher',
            'parent' => 'parent',
        ];

        $file = $map[$slug] ?? 'default';

        return asset('admin-assets/img/roles/'.$file.'.svg');
    }
}
