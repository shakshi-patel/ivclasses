<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Billing gateway driver
    |--------------------------------------------------------------------------
    |
    | Set BILLING_DRIVER explicitly: bypass | razorpay | stripe
    | If unset: local → bypass, otherwise → razorpay (fail closed without keys).
    | Production must never rely on bypass unless BILLING_ALLOW_BYPASS=true.
    |
    */
    'driver' => env('BILLING_DRIVER'),

    'currency' => env('BILLING_CURRENCY', 'INR'),

    'bypass' => [
        'auto_complete' => filter_var(
            env('BILLING_BYPASS_AUTO_COMPLETE', true),
            FILTER_VALIDATE_BOOLEAN
        ),
        'allow_outside_local' => filter_var(
            env('BILLING_ALLOW_BYPASS', false),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
