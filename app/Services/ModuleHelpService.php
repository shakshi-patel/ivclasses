<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\ModuleHelp;
use App\Models\ModuleHelpRole;
use App\Models\ModuleHelpStep;
use App\Models\User;
use App\Support\PlanModules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ModuleHelpService
{
    /**
     * @return array<string, string>
     */
    public function moduleOptions(): array
    {
        return ['institute_setup' => 'Institute first-login setup'] + PlanModules::crmCatalog();
    }

    /**
     * @return array<string, string>
     */
    public function roleOptions(): array
    {
        return [
            'institute_admin' => 'Institute Admin',
            'branch_manager' => 'Branch Manager',
            'teacher' => 'Teacher',
            'principal' => 'Principal',
            'accountant' => 'Accountant',
            'receptionist' => 'Receptionist',
            'parent' => 'Parent',
        ];
    }

    /**
     * @return Collection<int, ModuleHelp>
     */
    public function listAll(): Collection
    {
        return ModuleHelp::query()
            ->with(['steps', 'roleRows'])
            ->withCount('steps')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $steps
     */
    public function create(array $data, array $steps): ModuleHelp
    {
        return DB::transaction(function () use ($data, $steps) {
            $guide = ModuleHelp::query()->create([
                'module_key' => $data['module_key'],
                'title' => $data['title'],
                'intro' => $data['intro'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status' => $data['status'] ?? ModuleHelp::STATUS_ACTIVE,
                'is_onboarding' => (bool) ($data['is_onboarding'] ?? false),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->claimOnboarding($guide);
            $this->syncRoles($guide, $data['role_names'] ?? []);
            $this->syncSteps($guide, $steps);

            return $guide->fresh(['steps', 'roleRows']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $steps
     */
    public function update(ModuleHelp $guide, array $data, array $steps): ModuleHelp
    {
        return DB::transaction(function () use ($guide, $data, $steps) {
            $guide->update([
                'module_key' => $data['module_key'],
                'title' => $data['title'],
                'intro' => $data['intro'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status' => $data['status'] ?? $guide->status,
                'is_onboarding' => (bool) ($data['is_onboarding'] ?? false),
                'updated_by' => auth()->id(),
            ]);

            $this->claimOnboarding($guide);
            $this->syncRoles($guide, $data['role_names'] ?? []);
            $this->syncSteps($guide, $steps);

            return $guide->fresh(['steps', 'roleRows']);
        });
    }

    public function delete(ModuleHelp $guide): void
    {
        DB::transaction(function () use ($guide) {
            $guide->load('steps');
            foreach ($guide->steps as $step) {
                $this->deleteMedia($step->media_path);
            }
            $guide->steps()->delete();
            $guide->roleRows()->delete();
            $guide->delete();
        });
    }

    /**
     * Form defaults for create/edit Module Help blade (no @php business mapping).
     *
     * @return array{isEdit: bool, selectedRoles: list<string>, steps: list<array<string, mixed>>}
     */
    public function formState(?ModuleHelp $guide, array $old = []): array
    {
        $isEdit = (bool) $guide;
        $selectedRoles = $old['role_names'] ?? ($guide ? $guide->roleNames() : []);

        $steps = $old['steps'] ?? null;
        if (! is_array($steps)) {
            $steps = collect($guide?->steps ?? [])->map(fn ($s) => [
                'id' => $s->id ?? null,
                'title' => $s->title ?? '',
                'description' => $s->description ?? '',
                'media_type' => $s->media_type ?? 'none',
                'media_caption' => $s->media_caption ?? '',
                'embed_url' => $s->embed_url ?? '',
                'media_url' => method_exists($s, 'mediaUrl') ? $s->mediaUrl() : null,
                'media_original_name' => $s->media_original_name ?? null,
                'cta_label' => $s->cta_label ?? '',
                'cta_url' => $s->cta_url ?? '',
                'target' => $s->target_selector ?? '',
                'placement' => $s->placement ?? 'bottom',
                'is_required' => (bool) ($s->is_required ?? false),
                'completion_key' => $s->completion_key ?? '',
            ])->values()->all();
        }

        if ($steps === []) {
            $steps = [[
                'id' => null,
                'title' => '',
                'description' => '',
                'media_type' => 'none',
                'media_caption' => '',
                'embed_url' => '',
                'media_url' => null,
                'media_original_name' => null,
                'cta_label' => '',
                'cta_url' => '',
                'target' => '',
                'placement' => 'bottom',
                'is_required' => false,
                'completion_key' => '',
            ]];
        }

        return [
            'isEdit' => $isEdit,
            'selectedRoles' => $selectedRoles,
            'steps' => $steps,
        ];
    }

    public function toggleStatus(ModuleHelp $guide): ModuleHelp
    {
        $guide->update([
            'status' => $guide->status === ModuleHelp::STATUS_ACTIVE
                ? ModuleHelp::STATUS_INACTIVE
                : ModuleHelp::STATUS_ACTIVE,
            'updated_by' => auth()->id(),
        ]);

        return $guide->fresh();
    }

    /**
     * Role names used for Module Help targeting (Spatie + user_type fallbacks).
     *
     * @return list<string>
     */
    public function roleNamesFor(User $user): array
    {
        $names = [];

        try {
            $names = $user->getRoleNames()->map(fn ($n) => (string) $n)->all();
        } catch (\Throwable) {
            $names = [];
        }

        $type = $user->user_type instanceof UserType
            ? $user->user_type->value
            : (string) ($user->user_type ?? '');

        $allowed = array_keys($this->roleOptions());
        if ($type !== '' && in_array($type, $allowed, true)) {
            $names[] = $type;
        }

        if ($user->isPrincipal()) {
            $names[] = 'principal';
        }

        return array_values(array_unique(array_filter($names)));
    }

    public function eligibleGuideId(string $moduleKey, User $user): ?int
    {
        $roles = $this->roleNamesFor($user);
        if ($roles === []) {
            return null;
        }

        $id = ModuleHelp::query()
            ->where('module_key', $moduleKey)
            ->where('status', ModuleHelp::STATUS_ACTIVE)
            ->where('is_onboarding', false)
            ->whereHas('roleRows', fn ($q) => $q->whereIn('role_name', $roles))
            ->value('id');

        return $id ? (int) $id : null;
    }

    public function findActiveForUser(string $moduleKey, User $user): ?ModuleHelp
    {
        $roles = $this->roleNamesFor($user);
        if ($roles === []) {
            return null;
        }

        return ModuleHelp::query()
            ->where('module_key', $moduleKey)
            ->where('status', ModuleHelp::STATUS_ACTIVE)
            ->where('is_onboarding', false)
            ->whereHas('roleRows', fn ($q) => $q->whereIn('role_name', $roles))
            ->with(['steps', 'roleRows'])
            ->first();
    }

    /**
     * Lightweight boot payload for institute pages (no steps/media).
     *
     * @return array{guide_id: int, module_key: string, user_id: int, show_url: string}|null
     */
    public function runtimeForRequest(?User $user, ?string $routeName): ?array
    {
        if (! $user || $user->isPlatformUser()) {
            return null;
        }

        if (! \App\Support\ModuleHelpRegistry::isIndexRoute($routeName)) {
            return null;
        }

        // A setup highlight is already on this page. Do not also boot How it works,
        // or two tours stack and the screen cannot be clicked.
        if (request()->filled('onboarding_step')) {
            return null;
        }

        $moduleKey = \App\Support\ModuleHelpRegistry::resolve($routeName);
        if (! $moduleKey) {
            return null;
        }

        if ($moduleKey === 'dashboard' && app(InstituteOnboardingService::class)->state($user)['blocking']) {
            return null;
        }

        $guideId = $this->eligibleGuideId($moduleKey, $user);
        if (! $guideId) {
            return null;
        }

        return [
            'guide_id' => $guideId,
            'module_key' => $moduleKey,
            'user_id' => (int) $user->id,
            'show_url' => route('admin.module_help.show', ['moduleKey' => $moduleKey]),
        ];
    }

    /**
     * @param  array<int, string>  $roleNames
     */
    protected function syncRoles(ModuleHelp $guide, array $roleNames): void
    {
        $names = collect($roleNames)
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique()
            ->values();

        $guide->roleRows()->delete();

        foreach ($names as $name) {
            ModuleHelpRole::query()->create([
                'module_help_id' => $guide->id,
                'role_name' => $name,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     */
    protected function syncSteps(ModuleHelp $guide, array $steps): void
    {
        $keepIds = [];

        foreach (array_values($steps) as $index => $row) {
            $stepId = isset($row['id']) && (int) $row['id'] > 0 ? (int) $row['id'] : null;
            $mediaType = (string) ($row['media_type'] ?? ModuleHelpStep::MEDIA_NONE);
            if (! array_key_exists($mediaType, ModuleHelpStep::MEDIA_TYPES)) {
                $mediaType = ModuleHelpStep::MEDIA_NONE;
            }

            $ctaLabel = trim((string) ($row['cta_label'] ?? ''));
            $ctaUrl = trim((string) ($row['cta_url'] ?? ''));
            if ($ctaLabel === '' || $ctaUrl === '') {
                $ctaLabel = null;
                $ctaUrl = null;
            }

            $placement = (string) ($row['placement'] ?? 'bottom');
            if (! in_array($placement, ['top', 'bottom', 'left', 'right'], true)) {
                $placement = 'bottom';
            }

            $target = trim((string) ($row['target'] ?? ''));
            if ($target === '' || str_contains($target, '<') || str_contains($target, '>')) {
                $target = null;
            }

            $completionKey = trim((string) ($row['completion_key'] ?? ''));
            if (! array_key_exists($completionKey, InstituteOnboardingService::COMPLETION_KEYS)) {
                $completionKey = null;
            }

            $payload = [
                'title' => trim((string) ($row['title'] ?? '')),
                'description' => $row['description'] ?? null,
                'media_type' => $mediaType,
                'media_caption' => $mediaType === ModuleHelpStep::MEDIA_NONE
                    ? null
                    : ($row['media_caption'] ?? null),
                'embed_url' => $mediaType === ModuleHelpStep::MEDIA_EMBED
                    ? ($row['embed_url'] ?? null)
                    : null,
                'cta_label' => $ctaLabel,
                'cta_url' => $ctaUrl,
                'target_selector' => $target,
                'placement' => $placement,
                'is_required' => filter_var($row['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'completion_key' => $completionKey,
                'sort_order' => $index,
            ];

            if ($payload['title'] === '') {
                continue;
            }

            /** @var ModuleHelpStep|null $step */
            $step = $stepId
                ? $guide->steps()->whereKey($stepId)->first()
                : null;

            if ($step) {
                $step->fill($payload);
            } else {
                $step = new ModuleHelpStep(array_merge($payload, [
                    'module_help_id' => $guide->id,
                ]));
            }

            if ($mediaType === ModuleHelpStep::MEDIA_NONE) {
                $this->deleteMedia($step->media_path);
                $step->media_path = null;
                $step->media_original_name = null;
            } elseif ($mediaType === ModuleHelpStep::MEDIA_EMBED) {
                $this->deleteMedia($step->media_path);
                $step->media_path = null;
                $step->media_original_name = null;
            } else {
                $file = $row['file'] ?? null;
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $this->deleteMedia($step->media_path);
                    $step->media_path = $file->store('module-help/'.$guide->id, 'public');
                    $step->media_original_name = $file->getClientOriginalName();
                }
            }

            $step->save();
            $keepIds[] = $step->id;
        }

        $toDelete = $guide->steps()->whereNotIn('id', $keepIds ?: [0])->get();
        foreach ($toDelete as $step) {
            $this->deleteMedia($step->media_path);
            $step->delete();
        }
    }

    protected function claimOnboarding(ModuleHelp $guide): void
    {
        if (! $guide->is_onboarding) {
            return;
        }

        ModuleHelp::query()
            ->where('id', '!=', $guide->id)
            ->where('is_onboarding', true)
            ->update(['is_onboarding' => false]);
    }

    protected function deleteMedia(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
