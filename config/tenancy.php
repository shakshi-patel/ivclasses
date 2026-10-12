<?php

return [

    /*
    | Central hosts: marketing site + platform admin (no institute subdomain).
    */
    'central_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', 'localhost,127.0.0.1,ivclasses.test,www.ivclasses.test,iv_classes.test'))
    ))),

    /*
    | Optional dedicated platform host. If empty, any central domain works for Super Admin.
    */
    'platform_domain' => env('TENANCY_PLATFORM_DOMAIN', ''),

    /*
    | Spatie team_id used on the central/platform host.
    */
    'platform_team_id' => 0,
];
