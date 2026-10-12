<?php

namespace App\Services\Billing;

use App\Contracts\Billing\BillingGatewayInterface;
use App\Services\Billing\Gateways\BypassBillingGateway;
use App\Services\Billing\Gateways\RazorpayBillingGateway;
use App\Services\Billing\Gateways\StripeBillingGateway;
use InvalidArgumentException;
use RuntimeException;

class BillingGatewayManager
{
    public function driver(?string $driver = null): BillingGatewayInterface
    {
        $driver ??= $this->activeDriverName();

        if ($driver === 'bypass' && ! $this->bypassAllowed()) {
            throw new RuntimeException(
                'Billing bypass is disabled outside local. Set BILLING_DRIVER=razorpay|stripe with keys, or BILLING_ALLOW_BYPASS=true for staging only.'
            );
        }

        return match ($driver) {
            'bypass' => app(BypassBillingGateway::class),
            'razorpay' => app(RazorpayBillingGateway::class),
            'stripe' => app(StripeBillingGateway::class),
            default => throw new InvalidArgumentException("Unknown billing driver [{$driver}]."),
        };
    }

    public function activeDriverName(): string
    {
        $configured = config('billing.driver');

        if (filled($configured)) {
            return (string) $configured;
        }

        return app()->environment('local') ? 'bypass' : 'razorpay';
    }

    public function isBypassMode(): bool
    {
        return $this->activeDriverName() === 'bypass' && $this->bypassAllowed();
    }

    public function bypassAllowed(): bool
    {
        if (app()->environment('local')) {
            return true;
        }

        return (bool) config('billing.bypass.allow_outside_local', false);
    }
}
