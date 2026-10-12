<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\Billing\BillingGatewayManager;
use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingWebhookController extends Controller
{
    public function razorpay(Request $request, BillingGatewayManager $gateways, SubscriptionBillingService $billing): JsonResponse
    {
        $gateway = $gateways->driver('razorpay');
        $payload = $gateway->verifyWebhook($request);

        if (! $payload || ! ($payload['success'] ?? false)) {
            return response()->json(['received' => true], 200);
        }

        return $this->completeFromWebhook($payload, $billing);
    }

    public function stripe(Request $request, BillingGatewayManager $gateways, SubscriptionBillingService $billing): JsonResponse
    {
        $gateway = $gateways->driver('stripe');
        $payload = $gateway->verifyWebhook($request);

        if (! $payload || ! ($payload['success'] ?? false)) {
            return response()->json(['received' => true], 200);
        }

        return $this->completeFromWebhook($payload, $billing);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function completeFromWebhook(array $payload, SubscriptionBillingService $billing): JsonResponse
    {
        $gatewayPaymentId = $payload['gateway_payment_id'] ?? null;
        if (! $gatewayPaymentId) {
            return response()->json(['received' => true], 200);
        }

        $payment = SubscriptionPayment::query()
            ->where('gateway_payment_id', $gatewayPaymentId)
            ->orWhere('gateway_order_id', $gatewayPaymentId)
            ->first();

        if ($payment && ! $payment->isPaid()) {
            $billing->markPaid($payment, $payload);
        }

        return response()->json(['received' => true], 200);
    }
}
