<?php

namespace App\Contracts\Billing;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

interface BillingGatewayInterface
{
    public function driver(): string;

    public function isConfigured(): bool;

    /**
     * @return array{success: bool, gateway_order_id?: string, gateway_payment_id?: string, redirect_url?: string, client_payload?: array, message?: string}
     */
    public function initiateCheckout(SubscriptionPayment $payment): array;

    /**
     * @return array{success: bool, gateway_payment_id?: string, message?: string}|null
     */
    public function verifyWebhook(Request $request): ?array;
}
