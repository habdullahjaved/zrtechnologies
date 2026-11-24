<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin panel
    |--------------------------------------------------------------------------
    |
    | Staff sign in at /{path}. Multi-factor authentication is mandatory in
    | production. It can be relaxed locally so demo accounts are quick to use.
    |
    */

    'admin' => [
        'path' => env('ADMIN_PATH', 'admin'),
        'require_mfa' => (bool) env('ADMIN_REQUIRE_MFA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | First super admin
    |--------------------------------------------------------------------------
    |
    | Used by the AdminUserSeeder to create the owner's account. If no password
    | is set, a strong random one is generated and printed once.
    |
    */

    'super_admin' => [
        'name' => env('ADMIN_NAME', 'Muhammad Umar Shabbir'),
        'email' => env('ADMIN_EMAIL', 'mrumar@zrtechnologiesltd.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Indexing guard
    |--------------------------------------------------------------------------
    |
    | Search engines may only index the site when the app runs in production
    | AND indexing is switched on in Site settings. Every other environment
    | sends "noindex, nofollow" and a blocking robots.txt.
    |
    */

    'indexable_environments' => ['production'],

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Only set when the host puts a proxy/CDN in front of PHP (e.g. Cloudflare),
    | as a comma-separated list of IPs/ranges or "*". Left empty, proxy headers
    | are ignored so visitors cannot spoof their IP address.
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
