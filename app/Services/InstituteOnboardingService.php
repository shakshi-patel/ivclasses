<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\InstituteOnboardingSkip;
use App\Models\ModuleHelp;
use App\Models\ModuleHelpStep;
use App\Models\Standard;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class InstituteOnboardingService
{
    /** @var array<string, string> */
    public const COMPLETION_KEYS = [
        'branch' => 'Branch saved',
        'standard' => 'Standard saved',
        'subject' => 'Subject saved',
        'batch' => 'Batch saved',
        'teacher' => 'Teacher saved',
        'student' => 'Student saved',
    ];

    /**
     * @return array{
     *     applies: bool,
     *     blocking: bool,
     *     waiting: bool,
     *     title: string,
     *     intro: string,
     *     current: ?array<string, mixed>,
     *     reminders: list<array<string, mixed>>,
     *     done_required: int,
     *     total_required: int
     * }
     */
    public function state(User $user): array
    {
        $empty = [
            'applies' => false,
            'blocking' => false,
            'waiting' => false,
            'title' => '',
            'intro' => '',
            'current' => null,
            'reminders' => [],
            'done_required' => 0,
            'total_required' => 0,
        ];

        if (! $user->institute_id || $user->isPlatformUser()) {
            return $empty;
        }

        $guide = $this->guide();
        if (! $guide) {
            return $empty;
        }

        $instituteId = (int) $user->institute_id;
        $skipped = InstituteOnboardingSkip::query()
            ->where('institute_id', $instituteId)
            ->pluck('module_help_step_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $requiredDone = 0;
        $requiredTotal = 0;
        $current = null;
        $reminders = [];

        foreach ($guide->steps as $step) {
            $complete = $this->isComplete($instituteId, (string) $step->completion_key);
            $acknowledged = in_array((int) $step->id, $skipped, true);

            if ($step->is_required) {
                $requiredTotal++;
                if ($complete) {
                    $requiredDone++;
                }
                if ($current === null && ! ($complete && $acknowledged)) {
                    $current = $this->present($step, $complete, $requiredTotal);
                }
                continue;
            }

            if (! $complete && ! $acknowledged) {
                $reminders[] = $this->present($step, $complete, 0);
            }
        }

        $isAdmin = $user->user_type === UserType::InstituteAdmin;
        $blocking = $isAdmin && $current !== null;

        return [
            'applies' => true,
            'blocking' => $blocking,
            'waiting' => ! $isAdmin && $current !== null,
            'title' => $guide->title,
            'intro' => (string) ($guide->intro ?? ''),
            'current' => $blocking ? $current : null,
            'reminders' => ($isAdmin && $current === null) ? $reminders : [],
            'done_required' => $requiredDone,
            'total_required' => $requiredTotal,
        ];
    }

    public function skip(User $user, ModuleHelpStep $step): void
    {
        if (! $user->institute_id) {
            abort(422, 'This step is required.');
        }

        $guide = $this->guide();
        abort_unless($guide && (int) $step->module_help_id === (int) $guide->id, 404);

        if ($step->is_required && ! $this->isComplete((int) $user->institute_id, (string) $step->completion_key)) {
            abort(422, 'Save this record before leaving the step.');
        }

        InstituteOnboardingSkip::query()->firstOrCreate([
            'institute_id' => $user->institute_id,
            'module_help_step_id' => $step->id,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function highlight(User $user, ModuleHelpStep $step): ?array
    {
        if (! $user->institute_id || $user->user_type !== UserType::InstituteAdmin) {
            return null;
        }

        $guide = $this->guide();
        if (! $guide || (int) $step->module_help_id !== (int) $guide->id) {
            return null;
        }

        $already = $this->isComplete((int) $user->institute_id, (string) $step->completion_key);

        return [
            'id' => $step->id,
            'title' => $step->title,
            'description' => $step->description,
            'target' => $step->target_selector,
            'placement' => $step->placement ?: 'bottom',
            'required' => (bool) $step->is_required,
            'already' => $already,
            'dashboard_url' => route('dashboard'),
        ];
    }

    public function guide(): ?ModuleHelp
    {
        return ModuleHelp::query()
            ->where('is_onboarding', true)
            ->where('status', ModuleHelp::STATUS_ACTIVE)
            ->with('steps')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    public function isComplete(int $instituteId, string $key): bool
    {
        return match ($key) {
            'branch' => Branch::query()->where('institute_id', $instituteId)->exists(),
            'standard' => Standard::query()->where('institute_id', $instituteId)->exists(),
            'subject' => Subject::query()->where('institute_id', $instituteId)->exists(),
            'batch' => Batch::query()->where('institute_id', $instituteId)->exists(),
            'teacher' => Teacher::query()->where('institute_id', $instituteId)->exists(),
            'student' => Student::query()->where('institute_id', $instituteId)->exists(),
            default => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ModuleHelpStep $step, bool $complete, int $stepNumber): array
    {
        $already = $complete && (bool) $step->is_required;

        return [
            'id' => $step->id,
            'title' => $step->title,
            'description' => $step->description,
            'required' => (bool) $step->is_required,
            'complete' => $complete,
            'already' => $already,
            'can_skip' => $already || ! $step->is_required,
            'already_note' => $already ? $this->alreadyNote((string) $step->completion_key) : '',
            'step_number' => $stepNumber,
            'go_url' => $this->goUrl($step),
        ];
    }

    private function alreadyNote(string $key): string
    {
        $name = match ($key) {
            'branch' => 'branch',
            'standard' => 'standard',
            'subject' => 'subject',
            'batch' => 'batch',
            'teacher' => 'teacher',
            'student' => 'student',
            default => 'record',
        };

        return 'A '.$name.' is already added. Add another if you need one, or skip this step.';
    }

    private function goUrl(ModuleHelpStep $step): string
    {
        $base = $step->ctaHref();
        if (! $base) {
            $route = match ((string) $step->completion_key) {
                'branch' => 'admin.master.branch',
                'standard' => 'admin.master.standard',
                'subject' => 'admin.master.subject',
                'batch' => 'admin.master.batch',
                'teacher' => 'admin.master.teacher',
                'student' => 'admin.student',
                default => null,
            };
            $base = ($route && Route::has($route)) ? route($route) : route('dashboard');
        }

        $join = str_contains($base, '?') ? '&' : '?';

        return $base.$join.'onboarding_step='.$step->id;
    }
}
