<?php

use App\Enums\JobStatus;
use App\Livewire\TrackRepair;
use App\Models\Address;
use App\Models\AvailabilityWindow;
use App\Models\JobStatusChange;
use App\Models\RepairJob;
use App\Settings\SiteSettings;
use App\Support\Messaging\MessageVariables;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    $window = AvailabilityWindow::factory()->create(['weekday' => 2, 'starts_at' => '09:00', 'ends_at' => '12:00']);
    $this->job = RepairJob::factory()->create([
        'reference' => 'ZR-2026-000123',
        'collection_date' => '2026-12-01',
        'availability_window_id' => $window->id,
        'collection_window' => '9am–12pm',
    ]);
    $this->job->update(['collection_address_id' => Address::factory()->for($this->job->customer)->postcode('BB2 4NY')->create()->id]);
    $this->job->items()->create(['description' => 'Battery Replacement', 'price_pence' => 6900, 'warranty_days' => 180]);
    RateLimiter::clear('track-repair:127.0.0.1');
});

it('shows the booking page with the wizard', function () {
    $this->get(route('booking.create'))
        ->assertOk()
        ->assertSee('Book Your iPhone Repair')
        ->assertSeeLivewire('booking-wizard');
});

it('shows the thank-you page only through its signed link', function () {
    $this->get(route('booking.thanks', ['ref' => 'ZR-2026-000123']))->assertForbidden();

    $this->get(URL::signedRoute('booking.thanks', ['ref' => 'ZR-2026-000123']))
        ->assertOk()
        ->assertSee('You’re booked in')
        ->assertSee('ZR-2026-000123')
        ->assertSee('Tuesday 1 December 2026, 9am–12pm')
        ->assertSee('Add to Google Calendar')
        ->assertSee('noindex', false);
});

it('offers a calendar file for the collection', function () {
    $this->get(URL::signedRoute('booking.calendar', ['reference' => 'ZR-2026-000123']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('DTSTART:20261201T090000Z', false)
        ->assertSee('DTEND:20261201T120000Z', false)
        ->assertSee('SUMMARY:iPhone collection (ZR-2026-000123)', false);
});

it('finds a repair by reference and postcode', function () {
    Livewire::test(TrackRepair::class)
        ->set('reference', 'zr-2026-000123')
        ->set('postcode', 'bb24ny')
        ->call('track')
        ->assertHasNoErrors()
        ->assertRedirect(MessageVariables::trackUrl($this->job));
});

it('gives nothing away when the reference and postcode do not match', function () {
    Livewire::test(TrackRepair::class)
        ->set('reference', 'ZR-2026-000123')
        ->set('postcode', 'BB1 1AA')
        ->call('track')
        ->assertHasErrors('reference')
        ->assertSee('We could not find a repair with that reference and postcode');
});

it('limits tracking attempts', function () {
    $tracker = Livewire::test(TrackRepair::class)->set('reference', 'ZR-2026-999999')->set('postcode', 'BB1 1AA');

    foreach (range(1, 8) as $attempt) {
        $tracker->call('track');
    }

    $tracker->call('track')->assertSee('Too many attempts');
});

it('shows the customer their progress and the team’s updates', function () {
    $this->job->update(['status' => JobStatus::AwaitingParts]);
    JobStatusChange::factory()->for($this->job)->create(['to_status' => JobStatus::AwaitingParts, 'customer_message' => 'The new battery arrives tomorrow.', 'note' => 'Ordered from supplier X']);

    $this->get(route('track-repair.show', ['reference' => 'ZR-2026-000123']))->assertForbidden();

    $this->get(MessageVariables::trackUrl($this->job))
        ->assertOk()
        ->assertSee('Waiting for parts')
        ->assertSee('The new battery arrives tomorrow.')
        ->assertDontSee('Ordered from supplier X')
        ->assertSee('noindex', false);
});

it('shows the tracking, contact and business pages', function (string $route, string $heading) {
    $this->get(route($route))->assertOk()->assertSee($heading);
})->with([
    'track a repair' => ['track-repair', 'Track Your Repair'],
    'contact' => ['contact', 'Contact Us'],
    'business' => ['business', 'iPhone Repairs for Business'],
]);

it('keeps private customer pages out of search engines', function () {
    config(['zr.indexable_environments' => ['testing']]);
    $site = app(SiteSettings::class);
    $site->allow_indexing = true;
    $site->save();

    $this->get('/robots.txt')
        ->assertSee('Disallow: /book-repair/thank-you')
        ->assertSee('Disallow: /track-repair/');
});
