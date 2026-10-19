<?php

namespace App\Services\Billing;

use App\Enums\InstituteStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Institute;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionBillingService
{
    public function __construct(
        protected BillingGatewayManager $gateways,
    ) {}

    public function publicPlans()
    {
        return Plan::query()
            ->where('is_public', true)
            ->where('status', 'active')
            ->where('code', '!=', 'trial')
            ->orderBy('sort_order')
            ->get();
    }

    public function payablePlans()
    {
        return Plan::query()
            ->where('status', 'active')
            ->where('code', '!=', 'trial')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function collectOffline(
        Institute $institute,
        Plan $plan,
        string $billingCycle,
        ?User $actor = null,
        array $meta = [],
    ): array {
        $this->assertPayable($plan, $billingCycle);

        $amount = $this->amountFor($plan, $billingCycle);

        return DB::transaction(function () use ($institute, $plan, $billingCycle, $amount, $actor, $meta) {
            $payment = SubscriptionPayment::query()->create([
                'institute_id' => $institute->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'currency' => $plan->currency ?: config('billing.currency', 'INR'),
                'status' => PaymentStatus::Pending,
                'gateway' => 'offline',
                'meta_json' => array_filter([
                    'mode' => 'offline',
                    'reference' => $meta['reference'] ?? null,
                    'notes' => $meta['notes'] ?? null,
                    'collected_by' => $actor?->id,
                ]),
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            $payment->update([
                'invoice_no' => $this->nextInvoiceNo($payment),
            ]);

            return $this->markPaid($payment, [
                'gateway_order_id' => 'offline_'.$payment->id,
                'gateway_payment_id' => $meta['reference'] ?? ('offline_ref_'.$payment->id),
                'message' => 'Offline payment recorded. Plan activated for '.$institute->name.'.',
            ], $actor);
        });
    }

    public function checkout(Institute $institute, Plan $plan, string $billingCycle, ?User $actor = null): array
    {
        $this->assertPayable($plan, $billingCycle);

        $amount = $this->amountFor($plan, $billingCycle);

        $gatewayName = $this->gateways->activeDriverName();
        $gateway = $this->gateways->driver();

        return DB::transaction(function () use ($institute, $plan, $billingCycle, $amount, $gatewayName, $gateway, $actor) {
            $payment = SubscriptionPayment::query()->create([
                'institute_id' => $institute->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'currency' => $plan->currency ?: config('billing.currency', 'INR'),
                'status' => PaymentStatus::Pending,
                'gateway' => $gatewayName,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            $payment->update([
                'invoice_no' => $this->nextInvoiceNo($payment),
            ]);

            $result = $gateway->initiateCheckout($payment);

            if (! ($result['success'] ?? false)) {
                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'meta_json' => ['gateway_response' => $result],
                    'updated_by' => $actor?->id,
                ]);

                return [
                    'success' => false,
                    'payment' => $payment->fresh(),
                    'message' => $result['message'] ?? 'Payment could not be started.',
                ];
            }

            if ($gatewayName === 'bypass') {
                return $this->markPaid($payment, $result, $actor);
            }

            $payment->update([
                'gateway_order_id' => $result['gateway_order_id'] ?? null,
                'gateway_payment_id' => $result['gateway_payment_id'] ?? null,
                'meta_json' => ['gateway_response' => $result],
                'updated_by' => $actor?->id,
            ]);

            return [
                'success' => true,
                'payment' => $payment->fresh(['plan']),
                'redirect_url' => $result['redirect_url'] ?? null,
                'client_payload' => $result['client_payload'] ?? null,
                'message' => $result['message'] ?? 'Proceed to payment.',
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $gatewayData
     */
    public function markPaid(SubscriptionPayment $payment, array $gatewayData = [], ?User $actor = null): array
    {
        if ($payment->isPaid()) {
            return [
                'success' => true,
                'payment' => $payment,
                'message' => 'Payment already completed.',
            ];
        }

        return DB::transaction(function () use ($payment, $gatewayData, $actor) {
            $payment->refresh();
            $payment->load('plan', 'institute');

            $payment->update([
                'status' => PaymentStatus::Paid,
                'gateway_order_id' => $gatewayData['gateway_order_id'] ?? $payment->gateway_order_id,
                'gateway_payment_id' => $gatewayData['gateway_payment_id'] ?? $payment->gateway_payment_id,
                'paid_at' => now(),
                'meta_json' => array_merge($payment->meta_json ?? [], ['completion' => $gatewayData]),
                'updated_by' => $actor?->id,
            ]);

            $subscription = $this->activateSubscription($payment->institute, $payment->plan, $payment->billing_cycle, $payment);

            $payment->update(['subscription_id' => $subscription->id]);

            return [
                'success' => true,
                'payment' => $payment->fresh(['plan', 'subscription']),
                'subscription' => $subscription,
                'message' => $gatewayData['message']
                    ?? ($this->gateways->isBypassMode()
                        ? 'Plan activated (payment bypassed for development).'
                        : 'Payment successful. Plan activated.'),
            ];
        });
    }

    public function activateSubscription(
        Institute $institute,
        Plan $plan,
        string $billingCycle,
        ?SubscriptionPayment $payment = null,
    ): Subscription {
        $endsAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();
        $amount = $billingCycle === 'yearly'
            ? (float) ($plan->price_yearly ?? 0)
            : (float) ($plan->price_monthly ?? 0);

        $clear = [
            'status' => InstituteStatus::Active,
            'trial_ends_at' => null,
        ];
        // Unlimited plan fields should not keep an old custom cap.
        if ($plan->max_branches === null) {
            $clear['max_branches_override'] = null;
        }
        if ($plan->max_students === null) {
            $clear['max_students_override'] = null;
        }
        if ($plan->max_staff === null) {
            $clear['max_staff_override'] = null;
        }
        $institute->update($clear);

        return Subscription::query()->create([
            'institute_id' => $institute->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'status' => SubscriptionStatus::Active,
            'amount' => $payment?->amount ?? $amount,
            'payment_gateway' => $payment?->gateway ?? $this->gateways->activeDriverName(),
            'gateway_subscription_id' => $payment?->gateway_payment_id,
            'meta_json' => [
                'payment_id' => $payment?->id,
                'invoice_no' => $payment?->invoice_no,
            ],
        ]);
    }

    public function extendTrial(Institute $institute, int $days): void
    {
        $endsAt = now()->addDays($days);

        $institute->update([
            'status' => InstituteStatus::Trial,
            'trial_ends_at' => $endsAt,
        ]);

        $trialPlan = Plan::query()->where('code', 'trial')->first();
        if ($trialPlan) {
            Subscription::query()->create([
                'institute_id' => $institute->id,
                'plan_id' => $trialPlan->id,
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'status' => SubscriptionStatus::Trial,
                'amount' => 0,
            ]);
        }
    }

    public function paymentHistory(Institute $institute, int $limit = 20)
    {
        return SubscriptionPayment::query()
            ->with('plan')
            ->where('institute_id', $institute->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function recentPayments(int $limit = 40)
    {
        return SubscriptionPayment::query()
            ->with(['plan', 'institute', 'createdBy'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    protected function assertPayable(Plan $plan, string $billingCycle): void
    {
        if ($plan->code === 'trial') {
            throw new InvalidArgumentException('Trial plan cannot be purchased. Choose a paid plan.');
        }

        if (! in_array($billingCycle, ['monthly', 'yearly'], true)) {
            throw new InvalidArgumentException('Invalid billing cycle.');
        }
    }

    protected function amountFor(Plan $plan, string $billingCycle): float
    {
        return $billingCycle === 'yearly'
            ? (float) ($plan->price_yearly ?? 0)
            : (float) ($plan->price_monthly ?? 0);
    }

    protected function nextInvoiceNo(SubscriptionPayment $payment): string
    {
        return 'INV-'.now()->format('Y').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }
}
