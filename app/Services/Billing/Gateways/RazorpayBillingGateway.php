<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\Billing\BillingGatewayInterface;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay driver — fail-closed until Orders API + webhook signature are wired.
 * Never return a successful verifyWebhook payload without HMAC verification.
 */
class RazorpayBillingGateway implements BillingGatewayInterface
{
    public function driver(): string
    {
        return 'razorpay';
    }

    public function isConfigured(): bool
    {
        return filled(config('billing.razorpay.key_id'))
            && filled(config('billing.razorpay.key_secret'));
    }

    public function initiateCheckout(SubscriptionPayment $payment): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Razorpay is not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env.',
            ];
        }

        Log::info('Razorpay checkout refused — integration not enabled', [
            'payment_id' => $payment->id,
        ]);

        return [
            'success' => false,
            'message' => 'Razorpay checkout is not enabled yet. Use billing bypass only in local, or complete the Razorpay Orders integration.',
        ];
    }

    public function verifyWebhook(Request $request): ?array
    {
        // Intentionally null: incomplete signature verification must never mark payment paid.
        if (! $this->isConfigured()) {
            return null;
        }

        Log::warning('Razorpay webhook ignored — signature verification not implemented', [
            'has_signature' => $request->hasHeader('X-Razorpay-Signature'),
        ]);

        return null;
    }
}
