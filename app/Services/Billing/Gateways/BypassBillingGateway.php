<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\Billing\BillingGatewayInterface;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BypassBillingGateway implements BillingGatewayInterface
{
    public function driver(): string
    {
        return 'bypass';
    }

    public function isConfigured(): bool
    {
        return (bool) config('billing.bypass.auto_complete', true);
    }

    public function initiateCheckout(SubscriptionPayment $payment): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Bypass billing is disabled.',
            ];
        }

        return [
            'success' => true,
            'gateway_order_id' => 'bypass_order_'.Str::uuid(),
            'gateway_payment_id' => 'bypass_pay_'.Str::uuid(),
            'message' => 'Payment bypassed (development mode).',
        ];
    }

    public function verifyWebhook(Request $request): ?array
    {
        return null;
    }
}
