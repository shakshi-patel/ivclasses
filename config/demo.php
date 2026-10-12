<?php

return [

    'timezone' => env('DEMO_TIMEZONE', 'Asia/Kolkata'),

    'business_start' => env('DEMO_BUSINESS_START', '10:00'),

    'business_end' => env('DEMO_BUSINESS_END', '18:00'),

    'duration_minutes' => (int) env('DEMO_DURATION_MINUTES', 45),

    'gap_minutes' => (int) env('DEMO_GAP_MINUTES', 15),

    'event_title_prefix' => env('DEMO_EVENT_TITLE_PREFIX', 'IV Classes Demo'),

    'inquiry_code_prefix' => env('DEMO_INQUIRY_CODE_PREFIX', 'INQ'),

    'notification_email' => env('DEMO_NOTIFICATION_EMAIL'),

    /*
    | How many days the Yes / No links in the admin email stay valid.
    */
    'confirmation_link_days' => (int) env('DEMO_CONFIRMATION_LINK_DAYS', 7),

];
