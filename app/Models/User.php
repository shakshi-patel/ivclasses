<?php

namespace App\Models;

use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes {
        HasRoles::hasPermissionTo as spatieHasPermissionTo;
    }

    /** @var array<int>|null memoized per-request */
    protected ?array $deniedPermissionIds = null;

    protected $fillable = [
        'institute_id',
        'user_type',
        'name',
        'email',
        'phone',
        'password',
        'avatar_path',
        'must_change_password',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'email_verified_at',
        'registration_otp',
        'registration_otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'registration_otp',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'registration_otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'user_type' => UserType::class,
        ];
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function parentGuardian(): HasOne
    {
        return $this->hasOne(ParentGuardian::class, 'user_id');
    }

    public function isParent(): bool
    {
        return $this->user_type === UserType::Parent;
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Permissions explicitly denied on this user, overriding whatever their
     * role(s) would otherwise grant. Scoped to the current team (institute),
     * same convention as Spatie's own permissions()/roles() relations.
     */
    public function deniedPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permission_denials',
            'user_id',
            'permission_id'
        )->wherePivot('team_id', (int) (getPermissionsTeamId() ?? 0))->withTimestamps();
    }

    /**
     * @param  string|int|Permission|\BackedEnum  $permission
     */
    public function isPermissionDenied($permission, ?string $guardName = null): bool
    {
        try {
            $permission = $this->filterPermission($permission, $guardName);
        } catch (PermissionDoesNotExist) {
            return false;
        }

        if ($this->deniedPermissionIds === null) {
            $this->deniedPermissionIds = $this->deniedPermissions()
                ->pluck('permissions.id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return in_array((int) $permission->id, $this->deniedPermissionIds, true);
    }

    /**
     * Explicitly deny a permission for this user, overriding their role(s).
     *
     * @param  string|int|Permission|\BackedEnum  $permission
     */
    public function denyPermissionTo($permission, ?string $guardName = null): static
    {
        $permission = $this->filterPermission($permission, $guardName);
        $teamId = (int) (getPermissionsTeamId() ?? 0);

        DB::table('user_permission_denials')->updateOrInsert(
            ['team_id' => $teamId, 'user_id' => $this->id, 'permission_id' => $permission->id],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $this->deniedPermissionIds = null;
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this;
    }

    /**
     * Remove an explicit denial, letting the user's role(s) grant it again.
     *
     * @param  string|int|Permission|\BackedEnum  $permission
     */
    public function undenyPermissionTo($permission, ?string $guardName = null): static
    {
        $permission = $this->filterPermission($permission, $guardName);
        $teamId = (int) (getPermissionsTeamId() ?? 0);

        DB::table('user_permission_denials')
            ->where('team_id', $teamId)
            ->where('user_id', $this->id)
            ->where('permission_id', $permission->id)
            ->delete();

        $this->deniedPermissionIds = null;
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this;
    }

    /**
     * Overrides Spatie's HasPermissions::hasPermissionTo() to let an explicit
     * denial win over a role-granted permission (Spatie itself has no "deny"
     * concept — only additive grants).
     *
     * @param  string|int|Permission|\BackedEnum  $permission
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        if ($this->isPermissionDenied($permission, $guardName)) {
            return false;
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    public function isPlatformUser(): bool
    {
        // Prefer user_type — do not rely on hasRole() before Spatie team_id is bound.
        return $this->user_type === UserType::PlatformAdmin;
    }

    public function isTeacher(): bool
    {
        return $this->user_type === UserType::Teacher;
    }

    public function isPrincipal(): bool
    {
        if (! $this->institute_id) {
            return false;
        }

        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $previous = getPermissionsTeamId();
        $registrar->setPermissionsTeamId($this->institute_id);

        try {
            return $this->hasRole('principal');
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }

    public function isBranchManager(): bool
    {
        if ($this->user_type === UserType::BranchManager) {
            return true;
        }

        return Branch::query()->where('manager_user_id', $this->id)->exists();
    }

    /**
     * True when the user is linked to at least one active branch
     * (pivot assignment or manager_user_id).
     */
    public function hasActiveBranchAccess(): bool
    {
        if ($this->branches()->where('branches.status', 'active')->exists()) {
            return true;
        }

        return Branch::query()
            ->where('manager_user_id', $this->id)
            ->where('status', 'active')
            ->exists();
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return asset('storage/'.$this->avatar_path);
        }

        if ($this->isPrincipal()) {
            return asset('admin-assets/img/roles/principal.svg');
        }

        return match ($this->user_type) {
            UserType::PlatformAdmin => asset('admin-assets/img/roles/developer.svg'),
            UserType::InstituteAdmin => asset('admin-assets/img/roles/institute_admin.svg'),
            UserType::BranchManager => asset('admin-assets/img/roles/receptionist.svg'),
            UserType::InstituteStaff => asset('admin-assets/img/roles/receptionist.svg'),
            UserType::Teacher => asset('admin-assets/img/roles/teacher.svg'),
            UserType::Parent => asset('admin-assets/img/roles/parent.svg'),
            default => asset('admin-assets/img/roles/default.svg'),
        };
    }

    public function typeLabel(): string
    {
        if ($this->isPrincipal()) {
            return 'Principal';
        }

        return match ($this->user_type) {
            UserType::PlatformAdmin => 'Platform Admin',
            UserType::InstituteAdmin => 'Institute Admin',
            UserType::BranchManager => 'Branch Manager',
            UserType::InstituteStaff => 'Staff',
            UserType::Teacher => 'Teacher',
            UserType::Parent => 'Parent',
            UserType::Student => 'Student',
            default => 'User',
        };
    }

    public function assignedBatches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'batch_teacher', 'teacher_user_id', 'batch_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
