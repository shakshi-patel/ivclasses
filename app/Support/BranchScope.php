<?php

namespace App\Support;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Institute;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * PRD branch data boundary: Institute Admin sees all branches;
 * Principal / Branch Manager / Staff / Teacher see assigned branches only.
 */
class BranchScope
{
    /** @var array<int>|null */
    protected ?array $branchIds = null;

    public function __construct(protected readonly ?User $user) {}

    public static function for(?User $user = null): self
    {
        return new self($user ?? auth()->user());
    }

    public function isRestricted(): bool
    {
        if (! $this->user || $this->user->isPlatformUser()) {
            return false;
        }

        return $this->user->user_type !== UserType::InstituteAdmin;
    }

    /**
     * @return array<int>
     */
    public function branchIds(): array
    {
        if (! $this->isRestricted()) {
            return [];
        }

        if ($this->branchIds === null) {
            $this->branchIds = $this->user
                ->branches()
                ->where('branches.status', 'active')
                ->pluck('branches.id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->branchIds;
    }

    public function applyToBranches(Builder $query): Builder
    {
        if (! $this->isRestricted()) {
            return $query;
        }

        $ids = $this->branchIds();
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($query->getModel()->getTable().'.id', $ids);
    }

    public function applyToBatches(Builder $query): Builder
    {
        if (! $this->isRestricted()) {
            return $query;
        }

        $ids = $this->branchIds();
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $ids);
    }

    public function applyToBranchMembers(Builder $query): Builder
    {
        if (! $this->isRestricted()) {
            return $query;
        }

        $ids = $this->branchIds();
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('user.branches', fn (Builder $q) => $q->whereIn('branches.id', $ids));
    }

    public function branchesForInstitute(int $instituteId): Collection
    {
        $this->ensureDefaultBranch($instituteId);

        $query = Branch::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('name');

        return $this->applyToBranches($query)->get(['id', 'name']);
    }

    /**
     * Institutes are expected to always have at least one branch (the registration
     * flow creates one automatically). This is a safety net for institutes that
     * somehow ended up with zero — it lazily creates a single "Main" branch so
     * students/batches/staff/teachers can still be created against the institute.
     */
    protected function ensureDefaultBranch(int $instituteId): void
    {
        if (Branch::query()->where('institute_id', $instituteId)->exists()) {
            return;
        }

        $institute = Institute::query()->find($instituteId);
        if (! $institute) {
            return;
        }

        $admin = User::query()
            ->where('institute_id', $instituteId)
            ->where('user_type', UserType::InstituteAdmin)
            ->first();

        $branch = Branch::query()->create([
            'institute_id' => $institute->id,
            'name' => $institute->name.' — Main',
            'contact_person' => $admin?->name,
            'email' => $admin?->email ?? $institute->email,
            'phone' => $institute->phone,
            'city' => $institute->city,
            'state' => $institute->state,
            'manager_user_id' => $admin?->id,
            'status' => 'active',
        ]);

        if ($admin) {
            $admin->branches()->syncWithoutDetaching([
                $branch->id => ['is_primary' => true],
            ]);
        }
    }

    /**
     * @return array<int>
     */
    public function allowedBranchIdsForInstitute(int $instituteId): array
    {
        return $this->branchesForInstitute($instituteId)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function canAccessBranch(?int $branchId): bool
    {
        if (! $branchId) {
            return false;
        }

        if (! $this->isRestricted()) {
            return true;
        }

        return in_array($branchId, $this->branchIds(), true);
    }

    public function assertCanAccessBranch(?int $branchId): void
    {
        if (! $this->canAccessBranch($branchId)) {
            abort(404);
        }
    }

    public function sharesBranchWith(?User $otherUser): bool
    {
        if (! $otherUser) {
            return false;
        }

        if (! $this->isRestricted()) {
            return true;
        }

        $otherBranchIds = $otherUser->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();

        return count(array_intersect($this->branchIds(), $otherBranchIds)) > 0;
    }

    public function assertSharesBranchWith(?User $otherUser): void
    {
        if (! $this->sharesBranchWith($otherUser)) {
            abort(404);
        }
    }
}
