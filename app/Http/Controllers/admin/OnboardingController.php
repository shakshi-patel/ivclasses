<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Models\ModuleHelpStep;
use App\Services\InstituteOnboardingService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingController extends AdminController
{
    public function __construct(
        protected InstituteOnboardingService $onboarding
    ) {}

    public function show(Request $request, ModuleHelpStep $step): JsonResponse
    {
        $user = $request->user();
        $payload = $user ? $this->onboarding->highlight($user, $step) : null;

        if (! $payload) {
            return Ajax::error('Setup step is not available.', null, 404);
        }

        return Ajax::success('OK', $payload);
    }

    public function skip(Request $request, ModuleHelpStep $step): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->user_type === UserType::InstituteAdmin, 403);

        $this->onboarding->skip($user, $step);

        $already = $step->is_required
            && $this->onboarding->isComplete((int) $user->institute_id, (string) $step->completion_key);

        return redirect()
            ->route('dashboard')
            ->with('success', $already
                ? 'Skipped. Add another later from the menu if you need one.'
                : 'Reminder dismissed. You can still add this later from the menu.');
    }
}
