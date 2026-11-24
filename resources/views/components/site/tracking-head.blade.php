<meta name="csrf-token" content="{{ csrf_token() }}">
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    gtag('consent', 'default', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: 'denied',
        functionality_storage: 'granted',
        security_storage: 'granted',
        wait_for_update: 500
    });
    gtag('set', 'ads_data_redaction', true);
    gtag('set', 'url_passthrough', true);
    window.zrTracking = @json($config);
    window.zrConsent = null;
    (function () {
        var match = document.cookie.match(/(?:^|; )zr_consent=([^;]*)/);
        if (!match) return;
        var choice = new URLSearchParams(decodeURIComponent(match[1]));
        if (choice.get('v') !== window.zrTracking.policyVersion) return;
        var analytics = choice.get('a') === '1', marketing = choice.get('m') === '1';
        window.zrConsent = { analytics: analytics, marketing: marketing };
        gtag('consent', 'update', {
            analytics_storage: analytics ? 'granted' : 'denied',
            ad_storage: marketing ? 'granted' : 'denied',
            ad_user_data: marketing ? 'granted' : 'denied',
            ad_personalization: marketing ? 'granted' : 'denied'
        });
    })();
</script>
