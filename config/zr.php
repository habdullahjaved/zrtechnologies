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

    /*
    |--------------------------------------------------------------------------
    | Full-page cache
    |--------------------------------------------------------------------------
    |
    | Public pages are cached for anonymous visitors (see the CachePublicPage
    | middleware). The cache is cleared whenever content, prices or settings
    | are saved, and after each front-end build. The TTL is a safety net.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Applied to public pages (not the admin). Set CSP_REPORT_ONLY=true to
    | test a change without blocking anything.
    |
    */

    'csp' => [
        'enabled' => (bool) env('CSP_ENABLED', true),
        'report_only' => (bool) env('CSP_REPORT_ONLY', false),
    ],

    'page_cache' => [
        'enabled' => (bool) env('PAGE_CACHE_ENABLED', true),
        'store' => env('PAGE_CACHE_STORE'),
        'ttl' => (int) env('PAGE_CACHE_TTL', 21600),
    ],

];
