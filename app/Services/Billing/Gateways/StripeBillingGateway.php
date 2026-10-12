<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\Billing\BillingGatewayInterface;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stripe driver — fail-closed until Checkout Session + webhook signature are wired.
 * Never return a successful verifyWebhook payload without signature verification.
 */
class StripeBillingGateway implements BillingGatewayInterface
{
    public function driver(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return filled(config('billing.stripe.secret'));
    }

    public function initiateCheckout(SubscriptionPayment $payment): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Stripe is not configured. Set STRIPE_SECRET in .env.',
            ];
        }

        Log::info('Stripe checkout refused — integration not enabled', [
            'payment_id' => $payment->id,
        ]);

        return [
            'success' => false,
            'message' => 'Stripe checkout is not enabled yet. Use billing bypass only in local, or complete the Stripe Checkout integration.',
        ];
    }

    public function verifyWebhook(Request $request): ?array
    {
        // Intentionally null: incomplete signature verification must never mark payment paid.
        if (! $this->isConfigured()) {
            return null;
        }

        Log::warning('Stripe webhook ignored — signature verification not implemented', [
            'has_signature' => $request->hasHeader('Stripe-Signature'),
        ]);

        return null;
    }
}
