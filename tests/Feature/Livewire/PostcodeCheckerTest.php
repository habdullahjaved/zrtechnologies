<?php

use App\Livewire\PostcodeChecker;
use App\Models\AvailabilityWindow;
use App\Models\ServiceArea;
use App\Settings\BookingSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake(['api.postcodes.io/postcodes/*' => Http::response(['status' => 404, 'error' => 'Invalid postcode'], 404)]);

    ServiceArea::factory()->districts(['BB1', 'BB2'])->create(['name' => 'Blackburn', 'collection_fee_pence' => 0]);
    ServiceArea::factory()->districts(['BB5'])->create(['name' => 'Accrington', 'collection_fee_pence' => 500]);
    RateLimiter::clear('postcode-check:127.0.0.1');
});

afterEach(fn () => Carbon::setTestNow());

it('confirms collection with the fee and the next slot', function () {
    Carbon::setTestNow('2026-11-30 06:00'); // Monday
    AvailabilityWindow::factory()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00']);

    Livewire::test(PostcodeChecker::class)
        ->set('postcode', 'bb5')
        ->call('check')
        ->assertHasNoErrors()
        ->assertSee('Good news, we collect from BB5.')
        ->assertSee('Accrington')
        ->assertSee('£5')
        ->assertSee('Today, 9am–12pm')
        ->assertSeeHtml(e(route('booking.create', ['postcode' => 'BB5'])));
});

it('shows free collection when there is no fee', function () {
    Livewire::test(PostcodeChecker::class)
        ->set('postcode', 'BB1')
        ->call('check')
        ->assertSee('Blackburn')
        ->assertSee('Free');
});

it('offers to talk when the postcode is outside the area', function () {
    Livewire::test(PostcodeChecker::class)
        ->set('postcode', 'PR1')
        ->call('check')
        ->assertSee('Sorry, we don’t collect from PR1 yet.')
        ->assertSee('Message us')
        ->assertDontSee('post-in repair service');
});

it('mentions post-in repairs outside the area when they are offered', function () {
    $settings = app(BookingSettings::class);
    $settings->post_in_enabled = true;
    $settings->save();

    Livewire::test(PostcodeChecker::class)
        ->set('postcode', 'PR1')
        ->call('check')
        ->assertSee('post-in repair service');
});

it('asks again for missing or unknown postcodes', function () {
    Livewire::test(PostcodeChecker::class)
        ->call('check')
        ->assertHasErrors(['postcode' => 'required']);

    Livewire::test(PostcodeChecker::class)
        ->set('postcode', 'BB2 9ZZ')
        ->call('check')
        ->assertHasErrors('postcode')
        ->assertSee('We could not find that postcode')
        ->assertSet('result', null);
});

it('limits how many postcodes one visitor can check per minute', function () {
    $checker = Livewire::test(PostcodeChecker::class)->set('postcode', 'BB1');

    foreach (range(1, 15) as $attempt) {
        $checker->call('check')->assertHasNoErrors();
    }

    $checker->call('check')->assertHasErrors('postcode')->assertSee('Too many checks');
});

it('does not let the browser tamper with the result', function () {
    Livewire::test(PostcodeChecker::class)
        ->set('result', ['status' => 'covered', 'booking_url' => 'javascript:alert(1)']);
})->throws(CannotUpdateLockedPropertyException::class);
