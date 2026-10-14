<?php

namespace App\Http\Controllers\Admin;

use App\Models\Institute;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\BillingGatewayManager;
use App\Services\Billing\PlanLimitService;
use App\Services\Billing\SubscriptionBillingService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class BillingController extends AdminController
{
    public function __construct(
        protected SubscriptionBillingService $billing,
        protected PlanLimitService $limits,
        protected BillingGatewayManager $gateways,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && ($user->can('billing.view') || $user->isPlatformUser()), 403);

        if ($user->isPlatformUser()) {
            return $this->platformIndex($request);
        }

        $institute = $user->institute;
        abort_unless($institute, 404);

        $institute->load('currentSubscription.plan');
        $canManage = $user->can('billing.checkout') || $user->isPlatformUser();

        return view('admin.billing.index', [
            'institute' => $institute,
            'subscription' => $institute->currentSubscription,
            'plans' => $canManage ? $this->billing->publicPlans() : collect(),
            'payments' => $canManage ? $this->billing->paymentHistory($institute) : collect(),
            'limits' => $this->limits->limitsFor($institute),
            'gatewayDriver' => $this->gateways->activeDriverName(),
            'isBypassMode' => $this->gateways->isBypassMode(),
            'needsBilling' => $institute->needsBilling(),
            'canManageBilling' => $canManage,
        ]);
    }

    public function checkout(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->can('billing.checkout') || $user->isPlatformUser()), 403);

        if ($user->isPlatformUser()) {
            return $this->platformCheckout($request, $user);
        }

        $institute = $user->institute;
        abort_unless($institute, 404);

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);

        try {
            $result = $this->billing->checkout(
                $institute,
                $plan,
                $validated['billing_cycle'],
                $user,
            );
        } catch (InvalidArgumentException $e) {
            return $this->checkoutFailed($request, $e->getMessage());
        }

        return $this->checkoutResult($request, $result);
    }

    protected function platformIndex(Request $request): View
    {
        $selectedId = $request->integer('institute_id') ?: null;
        $institutes = Institute::query()
            ->with('currentSubscription.plan')
            ->orderBy('name')
            ->get();

        $selected = $selectedId
            ? $institutes->firstWhere('id', $selectedId)
            : null;

        return view('admin.billing.platform', [
            'institutes' => $institutes,
            'selected' => $selected,
            'plans' => $this->billing->payablePlans(),
            'payments' => $this->billing->recentPayments(),
            'gatewayDriver' => $this->gateways->activeDriverName(),
            'isBypassMode' => $this->gateways->isBypassMode(),
        ]);
    }

    protected function platformCheckout(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'institute_id' => ['required', 'exists:institutes,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'payment_mode' => ['required', 'in:offline,gateway'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $institute = Institute::query()->findOrFail($validated['institute_id']);
        $plan = Plan::query()->findOrFail($validated['plan_id']);

        try {
            $result = $validated['payment_mode'] === 'offline'
                ? $this->billing->collectOffline(
                    $institute,
                    $plan,
                    $validated['billing_cycle'],
                    $user,
                    [
                        'reference' => $validated['reference'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                    ],
                )
                : $this->billing->checkout(
                    $institute,
                    $plan,
                    $validated['billing_cycle'],
                    $user,
                );
        } catch (InvalidArgumentException $e) {
            return $this->checkoutFailed($request, $e->getMessage());
        }

        return $this->checkoutResult($request, $result, $institute->id);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function checkoutResult(Request $request, array $result, ?int $instituteId = null): JsonResponse|RedirectResponse
    {
        if (! ($result['success'] ?? false)) {
            return $this->checkoutFailed($request, $result['message'] ?? 'Payment failed.');
        }

        if (! empty($result['redirect_url']) && ! $request->expectsJson()) {
            return redirect()->away($result['redirect_url']);
        }

        $message = $result['message'] ?? 'Plan activated.';

        if ($request->expectsJson()) {
            return Ajax::success($message, [
                'payment' => $result['payment'] ?? null,
                'redirect_url' => $result['redirect_url'] ?? null,
            ]);
        }

        $params = $instituteId ? ['institute_id' => $instituteId] : [];

        return redirect()
            ->route('admin.billing.index', $params)
            ->with('success', $message);
    }

    protected function checkoutFailed(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return Ajax::error($message);
        }

        return back()->with('error', $message);
    }
}
