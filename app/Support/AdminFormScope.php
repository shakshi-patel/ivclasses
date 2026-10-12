<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Institute;
use App\Models\User;

/**
 * Controls institute/branch field visibility on admin add/edit forms.
 * Platform admin: both visible.
 * Institute admin: institute hidden, branch visible.
 * Branch-restricted staff: both hidden (auto-scoped).
 */
class AdminFormScope
{
    public function __construct(protected readonly ?User $user) {}

    public static function for(?User $user = null): self
    {
        return new self($user ?? auth()->user());
    }

    public function showInstituteField(): bool
    {
        return (bool) $this->user?->isPlatformUser();
    }

    public function showBranchField(): bool
    {
        if (! $this->user || $this->user->isPlatformUser()) {
            return true;
        }

        return ! BranchScope::for($this->user)->isRestricted();
    }

    public function defaultInstituteId(?int $fallback = null): ?int
    {
        if ($this->user && ! $this->user->isPlatformUser()) {
            return (int) $this->user->institute_id;
        }

        return $fallback;
    }

    public function defaultBranchId(?int $fallback = null): ?int
    {
        if ($this->showBranchField()) {
            return $fallback;
        }

        if ($fallback) {
            return (int) $fallback;
        }

        $ids = BranchScope::for($this->user)->branchIds();
        if ($ids !== []) {
            return $ids[0];
        }

        $branch = $this->user?->branches()->orderBy('branches.id')->first();

        return $branch ? (int) $branch->id : null;
    }

    public function instituteName(?int $instituteId = null): ?string
    {
        $id = $this->defaultInstituteId($instituteId);

        if (! $id) {
            return null;
        }

        return Institute::query()->whereKey($id)->value('name');
    }

    public function branchName(?int $branchId = null): ?string
    {
        $id = $this->defaultBranchId($branchId);

        if (! $id) {
            return null;
        }

        return Branch::query()->whereKey($id)->value('name');
    }
}
