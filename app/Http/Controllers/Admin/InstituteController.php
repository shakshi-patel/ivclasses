<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InstituteStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserType;
use App\Models\Institute;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\PlanLimitService;
use App\Services\Billing\SubscriptionBillingService;
use App\Support\Ajax;
use App\Support\PlanModules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstituteController extends AdminController
{
    public function index(Request $request): View
    {
        $query = Institute::query()
            ->with(['currentSubscription.plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $institutes = $query->latest()->paginate(25)->withQueryString();

        $plans = Plan::query()->where('status', 'active')->orderBy('sort_order')->get();
        $sellableModules = PlanModules::sellableCatalog();
        $isPlatform = true;

        return view('admin.institutes.index', compact('institutes', 'plans', 'sellableModules', 'isPlatform'));
    }

    public function show(Institute $institute, PlanLimitService $limits): JsonResponse
    {
        $institute->load(['currentSubscription.plan']);

        $admin = $institute->users()
            ->where('user_type', UserType::InstituteAdmin)
            ->orderBy('id')
            ->first(['id', 'name', 'email', 'phone']);

        $subscription = $institute->currentSubscription;
        $isTrial = $institute->status === InstituteStatus::Trial
            || ($subscription && $subscription->status === SubscriptionStatus::Trial);
        $trialEnds = $institute->trial_ends_at ?? $subscription?->ends_at;
        $daysLeft = $trialEnds ? (int) now()->startOfDay()->diffInDays($trialEnds->copy()->startOfDay(), false) : null;

        $html = view('admin.institutes.partials.view_body', [
            'institute' => $institute,
            'admin' => $admin,
            'subscription' => $subscription,
            'isTrial' => $isTrial,
            'trialEnds' => $trialEnds,
            'daysLeft' => $daysLeft,
            'trialExpired' => $isTrial && $trialEnds && $trialEnds->isPast(),
            'planModules' => PlanModules::fromPlan($institute),
            'effectiveModules' => PlanModules::for($institute),
            'extraModules' => PlanModules::expand($institute->modules_extra_json),
            'blockedModules' => PlanModules::expand($institute->modules_blocked_json),
            'limits' => $limits->limitsFor($institute),
            'sellableModules' => PlanModules::sellableCatalog(),
        ])->render();

        return Ajax::success('OK', [
            'name' => $institute->name,
            'html' => $html,
            'overrides' => [
                'modules_extra' => PlanModules::expand($institute->modules_extra_json),
                'modules_blocked' => PlanModules::expand($institute->modules_blocked_json),
                'plan_modules' => array_values(array_diff(
                    PlanModules::fromPlan($institute),
                    PlanModules::CORE
                )),
                'plan_name' => $institute->currentSubscription?->plan?->name,
                'max_branches_override' => $institute->max_branches_override,
                'max_students_override' => $institute->max_students_override,
                'max_staff_override' => $institute->max_staff_override,
                'plan_max_branches' => $institute->currentSubscription?->plan?->max_branches,
                'plan_max_students' => $institute->currentSubscription?->plan?->max_students,
                'plan_max_staff' => $institute->currentSubscription?->plan?->max_staff,
            ],
        ]);
    }

    public function suspend(Institute $institute): JsonResponse
    {
        $institute->update(['status' => InstituteStatus::Suspended]);

        return Ajax::success('Institute suspended.', $institute->fresh());
    }

    public function activate(Institute $institute): JsonResponse
    {
        $institute->loadMissing('currentSubscription');
        $subscription = $institute->currentSubscription;

        $hasCoverage = $subscription
            && $subscription->ends_at
            && $subscription->ends_at->isFuture()
            && in_array($subscription->status, [
                SubscriptionStatus::Active,
                SubscriptionStatus::Trial,
            ], true);

        if (! $hasCoverage) {
            return Ajax::error('Cannot activate: no valid subscription. Assign a plan first.');
        }

        $status = $subscription->status === SubscriptionStatus::Trial
            ? InstituteStatus::Trial
            : InstituteStatus::Active;

        $institute->update([
            'status' => $status,
            'trial_ends_at' => $status === InstituteStatus::Trial
                ? ($institute->trial_ends_at ?? $subscription->ends_at)
                : null,
        ]);

        return Ajax::success('Institute activated.', $institute->fresh('currentSubscription.plan'));
    }

    public function assignPlan(Request $request, Institute $institute): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'trial_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);
        $cycle = $validated['billing_cycle'] ?? 'monthly';
        $isTrialPlan = $plan->code === 'trial';
        $trialDays = (int) ($validated['trial_days'] ?? $plan->trial_days ?: 14);

        DB::transaction(function () use ($institute, $plan, $cycle, $isTrialPlan, $trialDays) {
            // New plan is unlimited for a resource → drop a stale numeric override
            // so Billing shows Unlimited instead of an old custom cap.
            $clear = [];
            if ($plan->max_branches === null && $institute->max_branches_override !== null) {
                $clear['max_branches_override'] = null;
            }
            if ($plan->max_students === null && $institute->max_students_override !== null) {
                $clear['max_students_override'] = null;
            }
            if ($plan->max_staff === null && $institute->max_staff_override !== null) {
                $clear['max_staff_override'] = null;
            }
            if ($clear !== []) {
                $institute->update($clear);
            }

            Subscription::query()
                ->where('institute_id', $institute->id)
                ->whereIn('status', [
                    SubscriptionStatus::Active->value,
                    SubscriptionStatus::Trial->value,
                ])
                ->update([
                    'status' => SubscriptionStatus::Cancelled,
                ]);

            if ($isTrialPlan) {
                $institute->update([
                    'status' => InstituteStatus::Trial,
                    'trial_ends_at' => now()->addDays($trialDays),
                    'modules_blocked_json' => null,
                ]);
                $endsAt = now()->addDays($trialDays);
                $subscriptionStatus = SubscriptionStatus::Trial;
            } else {
                $institute->update([
                    'status' => InstituteStatus::Active,
                    'trial_ends_at' => null,
                    'modules_blocked_json' => null,
                ]);
                $endsAt = $cycle === 'yearly' ? now()->addYear() : now()->addMonth();
                $subscriptionStatus = SubscriptionStatus::Active;
            }

            Subscription::query()->create([
                'institute_id' => $institute->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $cycle,
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'status' => $subscriptionStatus,
                'amount' => $cycle === 'yearly' ? ($plan->price_yearly ?? 0) : ($plan->price_monthly ?? 0),
            ]);
        });

        return Ajax::success('Plan assigned.', $institute->fresh('currentSubscription.plan'));
    }

    public function extendTrial(Request $request, Institute $institute, SubscriptionBillingService $billing): JsonResponse
    {
        $validated = $request->validate([
            'trial_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $billing->extendTrial($institute, (int) $validated['trial_days']);

        return Ajax::success('Trial extended.', $institute->fresh('currentSubscription.plan'));
    }

    public function updateOverrides(Request $request, Institute $institute): JsonResponse
    {
        $sellable = array_keys(PlanModules::sellableCatalog());

        $validated = $request->validate([
            'modules_extra' => ['nullable', 'array'],
            'modules_extra.*' => ['string', Rule::in($sellable)],
            'modules_blocked' => ['nullable', 'array'],
            'modules_blocked.*' => ['string', Rule::in($sellable)],
            'max_branches_override' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'max_students_override' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'max_staff_override' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        $extra = array_values(array_unique($validated['modules_extra'] ?? []));
        $blocked = array_values(array_unique($validated['modules_blocked'] ?? []));

        // Same module cannot be both gifted and blocked.
        $overlap = array_intersect($extra, $blocked);
        if ($overlap !== []) {
            return Ajax::error('A module cannot be both extra and blocked: '.implode(', ', $overlap));
        }

        $institute->update([
            'modules_extra_json' => $extra ?: null,
            'modules_blocked_json' => $blocked ?: null,
            'max_branches_override' => $validated['max_branches_override'] ?? null,
            'max_students_override' => $validated['max_students_override'] ?? null,
            'max_staff_override' => $validated['max_staff_override'] ?? null,
        ]);

        return Ajax::success('Institute overrides saved.', [
            'effective_modules' => PlanModules::for($institute->fresh()),
        ]);
    }
}
