<?php

use App\Enums\Channel;
use App\Enums\LeadType;
use App\Models\ConsentRecord;
use App\Models\Lead;
use App\Services\Attribution\AttributionTracker;
use App\Services\Tracking\ConsentState;
use App\Settings\ConsentSettings;
use Illuminate\Http\Request;

function consentCookie(bool $analytics, bool $marketing, string $version = '2026-11'): string
{
    return http_build_query(['v' => $version, 'a' => $analytics ? '1' : '0', 'm' => $marketing ? '1' : '0', 'id' => '0f9c6d2e-4b1a-4c8e-9a51-2d6f3b7c8e10']);
}

it('sets Consent Mode defaults to denied and shows the banner markup on every public page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee("gtag('consent', 'default'", false)
        ->assertSee("ad_storage: 'denied'", false)
        ->assertSee('x-data="cookieBanner"', false)
        ->assertSee('Accept all')
        ->assertSee('Reject all')
        ->assertSee('Cookie settings');
});

it('records each cookie choice and sets a readable consent cookie', function (string $action, array $input, bool $analytics, bool $marketing) {
    $response = $this->postJson(route('consent.store'), ['action' => $action, ...$input, 'page' => '/repair-prices?x=1'])
        ->assertOk()
        ->assertJson(['analytics' => $analytics, 'marketing' => $marketing]);

    $record = ConsentRecord::query()->sole();

    expect($record->action)->toBe($action)
        ->and($record->analytics)->toBe($analytics)
        ->and($record->marketing)->toBe($marketing)
        ->and($record->policy_version)->toBe('2026-11')
        ->and($record->page)->toBe('/repair-prices')
        ->and($record->ip_hash)->not->toBeNull();

    $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === ConsentState::COOKIE);
    parse_str($cookie->getValue(), $values);

    expect($cookie->isHttpOnly())->toBeFalse()
        ->and($values['a'])->toBe($analytics ? '1' : '0')
        ->and($values['m'])->toBe($marketing ? '1' : '0')
        ->and($values['id'])->toBe($record->consent_id);
})->with([
    'accept all' => [ConsentRecord::ACCEPT_ALL, ['analytics' => false, 'marketing' => false], true, true],
    'reject all' => [ConsentRecord::REJECT_ALL, ['analytics' => true, 'marketing' => true], false, false],
    'analytics only' => [ConsentRecord::CUSTOM, ['analytics' => true, 'marketing' => false], true, false],
]);

it('rejects an unknown consent action', function () {
    $this->postJson(route('consent.store'), ['action' => 'everything', 'analytics' => true, 'marketing' => true])->assertUnprocessable();

    expect(ConsentRecord::query()->count())->toBe(0);
});

it('treats a choice made under an older policy version as no choice', function () {
    $request = Request::create('/', cookies: [ConsentState::COOKIE => consentCookie(true, true, '2020-01')]);

    expect(ConsentState::fromRequest($request)->decided)->toBeFalse();

    $settings = app(ConsentSettings::class);
    $settings->policy_version = '2020-01';
    $settings->save();

    expect(ConsentState::fromRequest($request))->decided->toBeTrue()->marketing->toBeTrue();
});

it('keeps attribution for 90 days only with marketing consent', function () {
    $withConsent = $this->withUnencryptedCookies([ConsentState::COOKIE => consentCookie(true, true)])
        ->get('/?gclid=TEST123&utm_source=google&utm_medium=cpc');

    $withConsent->assertCookie(AttributionTracker::COOKIE);
    $stored = json_decode((string) $withConsent->getCookie(AttributionTracker::COOKIE)?->getValue(), true);
    expect($stored['last_touch']['gclid'])->toBe('TEST123');

    $this->flushSession();
    app('cookie')->flushQueuedCookies(); // the test app keeps one cookie jar across requests

    $this->withUnencryptedCookies([ConsentState::COOKIE => consentCookie(true, false)])
        ->get('/?gclid=TEST456')
        ->assertCookieMissing(AttributionTracker::COOKIE);
});

it('credits a returning visitor to the advert they clicked days ago', function () {
    $touch = ['gclid' => 'OLDCLICK', 'utm_source' => 'google', 'utm_medium' => 'cpc', 'landing_page' => '/', 'captured_at' => now()->subDays(5)->toIso8601String()];

    $this->withUnencryptedCookies([ConsentState::COOKIE => consentCookie(true, true)])
        ->withCookie(AttributionTracker::COOKIE, (string) json_encode(['first_touch' => $touch, 'last_touch' => $touch]))
        ->get('/repair-prices')
        ->assertOk();

    $attribution = app(AttributionTracker::class)->current(app(Request::class));

    expect($attribution->channel)->toBe(Channel::GoogleAds)
        ->and($attribution->lastTouch['gclid'])->toBe('OLDCLICK');
});

it('logs a tapped phone number as a lead with the browser event ID, once per visit', function () {
    $eventId = '3b1f9e2c-6d4a-4f8b-9c2e-1a7d5e3f9b20';

    $this->withHeaders(['Origin' => config('app.url')])
        ->postJson(route('tracking.event'), ['event' => 'click_to_call', 'event_id' => $eventId, 'location' => 'header'])
        ->assertNoContent();

    $lead = Lead::query()->sole();

    expect($lead->type)->toBe(LeadType::CallClick)
        ->and($lead->event_id)->toBe($eventId)
        ->and($lead->details['location'])->toBe('header');

    $this->withHeaders(['Origin' => config('app.url')])
        ->postJson(route('tracking.event'), ['event' => 'click_to_call', 'event_id' => '5c2a8d1e-7f3b-4a9c-8e1d-2b6f4a9c7d31'])
        ->assertNoContent();

    expect(Lead::query()->count())->toBe(1);
});

it('ignores click reports from other websites', function () {
    $this->withHeaders(['Origin' => 'https://evil.example'])
        ->postJson(route('tracking.event'), ['event' => 'click_whatsapp', 'event_id' => '3b1f9e2c-6d4a-4f8b-9c2e-1a7d5e3f9b20'])
        ->assertForbidden();

    expect(Lead::query()->count())->toBe(0);
});
