<?php

use App\Actions\Jobs\ChangeJobStatus;
use App\Enums\JobStatus;
use App\Enums\LeadType;
use App\Enums\TrackingPlatform;
use App\Enums\TrackingStatus;
use App\Http\Controllers\OfflineConversionExportController;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\RepairJob;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\Tracking\ConversionDispatcher;
use App\Services\Tracking\Hasher;
use App\Settings\TrackingSettings;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    // Tests can swap the Meta response by changing $this->metaResponse.
    $this->metaResponse = [['events_received' => 1], 200];
    Http::fake([
        'graph.facebook.com/*' => fn () => Http::response(...$this->metaResponse),
        'www.google-analytics.com/*' => Http::response('', 204),
    ]);

    $settings = app(TrackingSettings::class);
    $settings->meta_pixel_id = '1234567890';
    $settings->meta_capi_token = 'secret-token';
    $settings->ga4_measurement_id = 'G-TEST123';
    $settings->ga4_api_secret = 'ga-secret';
    $settings->save();
});

function consentedLead(array $attributes = [], bool $marketing = true, bool $analytics = true): Lead
{
    return Lead::factory()->create([
        'type' => LeadType::Quote,
        'email' => ' Sarah@Example.com ',
        'phone' => '+447912345678',
        'name' => 'Sarah Khan',
        'postcode' => 'BB2 4NY',
        'fbc' => 'fb.1.1700000000000.abc',
        'gclid' => 'TEST123',
        'ga_client_id' => '123456789.1700000000',
        'consent' => ['analytics' => $analytics, 'marketing' => $marketing, 'decided' => true],
        ...$attributes,
    ]);
}

it('sends a website lead to the Meta Conversions API, hashed and deduplicated by event ID', function () {
    $lead = consentedLead();

    app(ConversionDispatcher::class)->lead($lead, '203.0.113.9');

    Http::assertSent(function (HttpRequest $request) use ($lead): bool {
        $event = $request['data'][0];

        return str_contains($request->url(), 'graph.facebook.com/v23.0/1234567890/events')
            && str_contains($request->url(), 'access_token=secret-token')
            && $event['event_name'] === 'Lead'
            && $event['event_id'] === $lead->event_id
            && $event['action_source'] === 'website'
            && $event['user_data']['em'] === [hash('sha256', 'sarah@example.com')]
            && $event['user_data']['ph'] === [hash('sha256', '447912345678')]
            && $event['user_data']['zp'] === [hash('sha256', 'bb24ny')]
            && $event['user_data']['fbc'] === 'fb.1.1700000000000.abc'
            && $event['user_data']['client_ip_address'] === '203.0.113.9';
    });

    $logged = TrackingEvent::query()->sole();

    expect($logged->status)->toBe(TrackingStatus::Sent)
        ->and($logged->response_code)->toBe(200)
        ->and(json_encode($logged->payload))->not->toContain('sarah@example.com')
        ->and(json_encode($logged->payload))->not->toContain('203.0.113.9');
});

it('sends nothing to Meta without marketing consent', function () {
    app(ConversionDispatcher::class)->lead(consentedLead(marketing: false));

    Http::assertNothingSent();
    expect(TrackingEvent::query()->sole()->status)->toBe(TrackingStatus::SkippedNoConsent);
});

it('logs but does not send when Meta is not set up', function () {
    $settings = app(TrackingSettings::class);
    $settings->meta_capi_token = null;
    $settings->save();

    app(ConversionDispatcher::class)->lead(consentedLead());

    Http::assertNothingSent();
    expect(TrackingEvent::query()->sole()->status)->toBe(TrackingStatus::Skipped);
});

it('never sends unfinished bookings or staff-added leads', function () {
    app(ConversionDispatcher::class)->lead(consentedLead(['is_partial' => true]));
    app(ConversionDispatcher::class)->lead(consentedLead(['type' => LeadType::Manual]));

    Http::assertNothingSent();
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('sends a booking as Lead and Schedule with the same event ID', function () {
    $lead = consentedLead(['type' => LeadType::Booking, 'estimated_value_pence' => 8900]);

    app(ConversionDispatcher::class)->lead($lead);

    expect(TrackingEvent::query()->pluck('event_name')->sort()->values()->all())->toBe(['Lead', 'Schedule']);
    Http::assertSent(fn (HttpRequest $request): bool => $request['data'][0]['custom_data']['value'] == 89);
});

it('marks a send as failed when Meta rejects it', function () {
    $this->metaResponse = [['error' => ['message' => 'Invalid parameter']], 400];

    app(ConversionDispatcher::class)->lead(consentedLead());

    $logged = TrackingEvent::query()->sole();

    expect($logged->status)->toBe(TrackingStatus::Failed)
        ->and($logged->error)->toBe('Invalid parameter');
});

it('records a completed repair as an offline conversion on every platform, once', function () {
    $this->actingAs(User::factory()->create());
    $lead = consentedLead(['type' => LeadType::Booking]);
    $job = RepairJob::factory()->for($lead)->for(Customer::factory()->create(['email' => 'sarah@example.com']))->create([
        'status' => JobStatus::Returned,
        'final_price_pence' => 12900,
    ]);

    app(ChangeJobStatus::class)->handle($job, JobStatus::Completed, notifyCustomer: false);

    $events = TrackingEvent::query()->get()->keyBy(fn (TrackingEvent $event) => $event->platform->value);

    expect($events)->toHaveCount(3)
        ->and($events['meta']->event_name)->toBe('Purchase')
        ->and($events['meta']->status)->toBe(TrackingStatus::Sent)
        ->and($events['ga4']->event_name)->toBe('purchase')
        ->and($events['ga4']->status)->toBe(TrackingStatus::Sent)
        ->and($events['google_ads']->status)->toBe(TrackingStatus::Exported)
        ->and($events['google_ads']->payload['gclid'])->toBe('TEST123')
        ->and($events['google_ads']->payload['value'])->toBe('129.00');

    Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), 'mp/collect')
        && $request['client_id'] === '123456789.1700000000'
        && $request['events'][0]['params']['transaction_id'] === $job->reference
        && $request['events'][0]['params']['value'] == 129);

    // Completing again (e.g. after a correction) never sends a second conversion.
    app(ConversionDispatcher::class)->completedJob($job->fresh());
    expect(TrackingEvent::query()->count())->toBe(3);
});

it('exports completed repairs for Google Ads behind the secret link', function () {
    TrackingEvent::factory()->create([
        'platform' => TrackingPlatform::GoogleAds,
        'kind' => TrackingEvent::KIND_OFFLINE,
        'status' => TrackingStatus::Exported,
        'event_name' => 'Repair Completed',
        'payload' => ['gclid' => 'TEST123', 'gbraid' => null, 'wbraid' => null, 'email' => Hasher::email('a@b.com'), 'phone' => null, 'conversion_time' => '2026-11-25 14:05:00', 'value' => '129.00', 'currency' => 'GBP'],
    ]);

    $this->get(route('exports.offline-conversions', ['token' => 'wrong']))->assertNotFound();

    $csv = $this->get(OfflineConversionExportController::url(app(TrackingSettings::class)))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->getContent();

    $lines = explode("\n", trim($csv));

    expect($lines[0])->toBe('Parameters:TimeZone=Europe/London')
        ->and($lines[1])->toBe('"Google Click ID",GBRAID,WBRAID,Email,"Phone Number","Conversion Name","Conversion Time","Conversion Value","Conversion Currency"')
        ->and($lines[2])->toBe('TEST123,,,'.hash('sha256', 'a@b.com').',,"Repair Completed","2026-11-25 14:05:00",129.00,GBP');
});

it('normalises personal data before hashing it', function () {
    expect(Hasher::email('  Sarah@Example.COM '))->toBe(hash('sha256', 'sarah@example.com'))
        ->and(Hasher::phone('07912 345678'))->toBe(hash('sha256', '447912345678'))
        ->and(Hasher::phone('+44 7912 345678'))->toBe(hash('sha256', '447912345678'))
        ->and(Hasher::name(' O’Brien '))->toBe(hash('sha256', 'obrien'))
        ->and(Hasher::postcode('bb2 4ny'))->toBe(hash('sha256', 'bb24ny'))
        ->and(Hasher::email(null))->toBeNull()
        ->and(Hasher::phone('not a phone'))->toBeNull();
});
