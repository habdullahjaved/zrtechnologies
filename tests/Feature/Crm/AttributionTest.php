<?php

use App\Data\AttributionData;
use App\Enums\Channel;
use App\Services\Attribution\AttributionTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

afterEach(fn () => Carbon::setTestNow());

function currentAttribution(): AttributionData
{
    return app(AttributionTracker::class)->current(app(Request::class));
}

it('captures click IDs and UTM tags from the landing page', function () {
    $this->get('/?gclid=TEST123&utm_source=google&utm_medium=cpc&utm_campaign=test')->assertOk();

    $attribution = currentAttribution();
    $columns = $attribution->toLeadAttributes();

    expect($attribution->channel)->toBe(Channel::GoogleAds)
        ->and($columns['gclid'])->toBe('TEST123')
        ->and($columns['utm_source'])->toBe('google')
        ->and($columns['utm_medium'])->toBe('cpc')
        ->and($columns['utm_campaign'])->toBe('test')
        ->and($columns['landing_page'])->toBe('/?gclid=TEST123&utm_source=google&utm_medium=cpc&utm_campaign=test')
        ->and($columns['attribution']['pages'][0]['path'])->toBe('/');
});

it('builds the Meta fbc value from fbclid and credits Meta ads', function () {
    Carbon::setTestNow('2026-11-30 10:00:00');

    $this->get('/?fbclid=IwAR0abc')->assertOk();

    $attribution = currentAttribution();

    expect($attribution->channel)->toBe(Channel::MetaAds)
        ->and($attribution->fbc)->toBe('fb.1.'.(Carbon::now()->getTimestamp() * 1000).'.IwAR0abc');
});

it('credits Google Business Profile for the tagged GBP link', function () {
    $this->get('/?utm_source=google&utm_medium=organic&utm_campaign=gbp')->assertOk();

    expect(currentAttribution()->channel)->toBe(Channel::GoogleBusinessProfile);
});

it('keeps the first touch and the last campaign, ignoring later direct visits', function () {
    $this->get('/', ['referer' => 'https://www.google.co.uk/'])->assertOk();
    $this->get('/repair-prices?gclid=ABC')->assertOk();
    $this->get('/areas-we-cover')->assertOk();

    $attribution = currentAttribution();

    expect($attribution->firstTouchChannel)->toBe(Channel::GoogleOrganic)
        ->and($attribution->channel)->toBe(Channel::GoogleAds)
        ->and(array_column($attribution->pages, 'path'))->toBe(['/', '/repair-prices', '/areas-we-cover']);
});

it('stores outside referrers without their query string, and ignores our own pages', function () {
    $this->get('/', ['referer' => 'https://www.lancashiretelegraph.co.uk/news/story?email=someone@example.com'])->assertOk();

    expect(currentAttribution()->lastTouch['referrer'])->toBe('https://www.lancashiretelegraph.co.uk/news/story');

    $this->get('/repair-prices', ['referer' => config('app.url').'/'])->assertOk();

    expect(currentAttribution()->channel)->toBe(Channel::Referral);
});

it('reads the GA4 client ID and Meta browser cookie when present', function () {
    $this->withUnencryptedCookies(['_ga' => 'GA1.1.123456789.1700000000', '_fbp' => 'fb.1.1700000000000.987654321'])
        ->get('/')
        ->assertOk();

    $attribution = currentAttribution();

    expect($attribution->gaClientId)->toBe('123456789.1700000000')
        ->and($attribution->fbp)->toBe('fb.1.1700000000000.987654321');
});

it('does not track admin pages', function () {
    $this->get('/'.config('zr.admin.path').'/login?utm_source=test');

    expect(session(AttributionTracker::SESSION_KEY))->toBeNull();
});

it('describes the device', function (string $userAgent, string $type) {
    expect(AttributionTracker::deviceType($userAgent))->toBe($type);
})->with([
    'iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148', 'mobile'],
    'iPad' => ['Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) AppleWebKit/605.1.15', 'tablet'],
    'Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36', 'desktop'],
]);
