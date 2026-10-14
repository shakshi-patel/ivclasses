<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Billing\SubscriptionBillingService;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __invoke(SubscriptionBillingService $billing): View
    {
        return view('website.pricing', [
            'plans' => $billing->publicPlans(),
        ]);
    }
}
