<?php

namespace App\Models;

use App\Enums\InstituteStatus;
use App\Enums\SubscriptionStatus;
use App\Support\Traits\HasSoftDeleteWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Institute extends Model
{
    use HasSoftDeleteWithUser;

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'slug',
        'email',
        'phone',
        'logo_path',
        'signature_path',
        'stamp_path',
        'address',
        'city',
        'state',
        'pincode',
        'gstin',
        'timezone',
        'status',
        'trial_ends_at',
        'onboarded_at',
        'settings_json',
        'modules_extra_json',
        'modules_blocked_json',
        'max_branches_override',
        'max_students_override',
        'max_staff_override',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'settings_json' => 'array',
            'modules_extra_json' => 'array',
            'modules_blocked_json' => 'array',
            'max_branches_override' => 'integer',
            'max_students_override' => 'integer',
            'max_staff_override' => 'integer',
            'status' => InstituteStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Institute $institute) {
            $institute->uuid ??= (string) Str::uuid();
            $institute->code ??= self::nextCode();
            $institute->slug ??= Str::slug($institute->name).'-'.Str::lower(Str::random(4));
        });
    }

    public static function nextCode(): string
    {
        return \App\Support\SequenceGenerator::global('institute_code', function () {
            $n = self::withTrashed()->count() + 1;

            return 'IVC-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function standards(): HasMany
    {
        return $this->hasMany(Standard::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function isSuspended(): bool
    {
        return $this->status === InstituteStatus::Suspended;
    }

    public function isCancelled(): bool
    {
        return $this->status === InstituteStatus::Cancelled;
    }

    public function canAccessCrm(): bool
    {
        return in_array($this->status, [InstituteStatus::Trial, InstituteStatus::Active], true);
    }

    public function isTrialExpired(): bool
    {
        return $this->status === InstituteStatus::Trial
            && $this->trial_ends_at
            && $this->trial_ends_at->isPast();
    }

    public function isSubscriptionExpired(): bool
    {
        if ($this->status !== InstituteStatus::Active) {
            return false;
        }

        return ! $this->hasValidCoverage();
    }

    /**
     * True when trial/active institute has a non-expired subscription (or future trial end).
     */
    public function hasValidCoverage(): bool
    {
        $this->loadMissing('currentSubscription');

        if ($this->status === InstituteStatus::Trial) {
            return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
        }

        if ($this->status !== InstituteStatus::Active) {
            return false;
        }

        $subscription = $this->currentSubscription;
        if (! $subscription || ! $subscription->ends_at) {
            return false;
        }

        if ($subscription->ends_at->isPast()) {
            return false;
        }

        return in_array($subscription->status, [
            SubscriptionStatus::Active,
            SubscriptionStatus::Trial,
        ], true);
    }

    public function needsBilling(): bool
    {
        if (! in_array($this->status, [InstituteStatus::Trial, InstituteStatus::Active], true)) {
            return false;
        }

        return ! $this->hasValidCoverage();
    }

    public function hasModule(string $module): bool
    {
        return \App\Support\PlanModules::allows($this, $module);
    }

    /**
     * @return list<string>
     */
    public function enabledModules(): array
    {
        return \App\Support\PlanModules::for($this);
    }

    public function logoUrl(?string $fallback = null): ?string
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }

        return $fallback;
    }

    public function logoMiniUrl(?string $fallback = null): ?string
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }

        return $fallback;
    }

    public function hasCustomLogo(): bool
    {
        return filled($this->logo_path);
    }

    public function signatureUrl(?string $fallback = null): ?string
    {
        if ($this->signature_path) {
            return asset('storage/'.$this->signature_path);
        }

        return $fallback;
    }

    public function stampUrl(?string $fallback = null): ?string
    {
        if ($this->stamp_path) {
            return asset('storage/'.$this->stamp_path);
        }

        return $fallback;
    }

    /**
     * @return array{primary: string, primary_dark: string, secondary: string, secondary_dark: string}
     */
    public function themeColors(): array
    {
        $defaults = [
            'primary' => '#0f5c4d',
            'primary_dark' => '#08483d',
            'secondary' => '#d9a441',
            'secondary_dark' => '#b7862a',
        ];

        $theme = $this->settings_json['theme'] ?? [];
        if (! is_array($theme)) {
            return $defaults;
        }

        $hex = fn ($value) => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : null;

        foreach ($defaults as $key => $default) {
            $defaults[$key] = $hex($theme[$key] ?? null) ?? $default;
        }

        return $defaults;
    }

    /**
     * CSS custom properties (and their RGB channel variants) to override
     * the default brand palette in public/css/iv-tokens.css for this institute.
     *
     * @return array<string, string>
     */
    public function themeCssVariables(): array
    {
        $colors = $this->themeColors();

        $rgb = fn (string $hex) => implode(', ', sscanf(ltrim($hex, '#'), '%02x%02x%02x'));

        return [
            '--iv-primary' => $colors['primary'],
            '--iv-primary-dark' => $colors['primary_dark'],
            '--iv-secondary' => $colors['secondary'],
            '--iv-secondary-dark' => $colors['secondary_dark'],
            '--iv-primary-rgb' => $rgb($colors['primary']),
            '--iv-primary-dark-rgb' => $rgb($colors['primary_dark']),
            '--iv-secondary-rgb' => $rgb($colors['secondary']),
        ];
    }

    /**
     * @return list<string>
     */
    public function feesReceiptTerms(): array
    {
        $terms = $this->settings_json['fees_receipt_terms'] ?? [];
        if (! is_array($terms)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($term) => is_array($term) ? trim((string) ($term['title'] ?? '')) : trim((string) $term),
            $terms
        )));
    }
}
