<?php

use App\Enums\Channel;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Livewire\BookingWizard;
use App\Models\AvailabilityWindow;
use App\Models\Customer;
use App\Models\DeviceModel;
use App\Models\Lead;
use App\Models\PartQuality;
use App\Models\RepairJob;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\ServiceArea;
use App\Notifications\BookingConfirmed;
use App\Notifications\StaffAlert;
use App\Settings\BookingSettings;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-11-30 06:00'); // Monday
    Notification::fake();
    Http::preventStrayRequests();
    Http::fake([
        'api.postcodes.io/postcodes/BB2*' => Http::response(postcodesIoResult('BB2 4NY')),
        'api.postcodes.io/postcodes/PR1*' => Http::response(postcodesIoResult('PR1 2HE', 53.7632, -2.7031)),
    ]);
    RateLimiter::clear('booking-postcode:127.0.0.1');
    RateLimiter::clear('booking:127.0.0.1');

    $this->area = ServiceArea::factory()->districts(['BB1', 'BB2'])->create(['name' => 'Blackburn', 'collection_fee_pence' => 500]);
    $this->morning = AvailabilityWindow::factory()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00', 'capacity' => 2]);

    $this->model = DeviceModel::factory()->create(['name' => 'iPhone 15', 'slug' => 'iphone-15']);
    $this->screen = RepairType::factory()->create(['name' => 'Screen Replacement', 'slug' => 'screen-replacement', 'sort' => 10]);
    $this->battery = RepairType::factory()->create(['name' => 'Battery Replacement', 'slug' => 'battery-replacement', 'sort' => 20]);
    $this->standard = PartQuality::factory()->create(['name' => 'Standard', 'slug' => 'standard', 'sort' => 1]);
    $this->premium = PartQuality::factory()->create(['name' => 'Premium', 'slug' => 'premium', 'sort' => 2]);

    RepairPrice::factory()->for($this->model)->for($this->screen)->for($this->standard)->create(['price_pence' => 8900, 'warranty_days' => 90]);
    $this->premiumScreen = RepairPrice::factory()->for($this->model)->for($this->screen)->for($this->premium)->create(['price_pence' => 14900, 'warranty_days' => 365]);
    RepairPrice::factory()->for($this->model)->for($this->battery)->create(['price_pence' => 6900, 'warranty_days' => 180]);

    $this->slotKey = '2026-11-30:'.$this->morning->id;
});

afterEach(fn () => Carbon::setTestNow());

/**
 * Walks the wizard to the review step with valid answers.
 */
function bookingAtReview(): Testable
{
    $test = test();

    return Livewire::test(BookingWizard::class)
        ->set('postcode', 'bb2 4ny')
        ->call('checkPostcode')
        ->assertSet('step', 2)
        ->call('chooseModel', 'iphone-15')
        ->set('colour', 'Midnight')
        ->call('submitDevice')
        ->assertSet('step', 3)
        ->set('selectedRepairs', [(string) $test->screen->id, (string) $test->battery->id])
        ->set('qualities.'.$test->screen->id, (string) $test->premium->id)
        ->call('submitRepairs')
        ->assertSet('step', 4)
        ->set('slot', $test->slotKey)
        ->call('submitCollection')
        ->assertSet('step', 5)
        ->set('firstName', 'Sarah')
        ->set('lastName', 'Ahmed')
        ->set('phone', '07912 345678')
        ->set('email', 'Sarah@Example.com')
        ->set('line1', '12 King Street')
        ->set('town', 'Blackburn')
        ->set('acceptTerms', true)
        ->set('startStraightAway', true)
        ->call('submitDetails')
        ->assertHasNoErrors()
        ->assertSet('step', 6);
}

it('books a repair end to end: customer, lead, job, prices and emails', function () {
    $wizard = bookingAtReview()
        ->assertSee('Estimated total')
        ->assertSee('£223'); // 149 + 69 + 5 collection

    $this->travel(10)->seconds();
    $wizard->call('confirm')->assertHasNoErrors();

    $job = RepairJob::query()->with(['items', 'lead', 'customer', 'collectionAddress'])->sole();

    expect($job->reference)->toMatch('/^ZR-2026-\d{6}$/')
        ->and($job->status)->toBe(JobStatus::Booked)
        ->and($job->collection_date->toDateString())->toBe('2026-11-30')
        ->and($job->collection_window)->toBe('9am–12pm')
        ->and($job->quoted_price_pence)->toBe(21800)
        ->and($job->collection_fee_pence)->toBe(500)
        ->and($job->device_colour)->toBe('Midnight')
        ->and($job->start_within_cancellation_period)->toBeTrue()
        ->and($job->terms_accepted_at)->not->toBeNull()
        ->and($job->items->pluck('description')->all())->toBe(['Screen Replacement (Premium)', 'Battery Replacement'])
        ->and($job->items->first()->warranty_days)->toBe(365)
        ->and($job->customer->email)->toBe('sarah@example.com')
        ->and($job->customer->phone)->toBe('+447912345678')
        ->and($job->collectionAddress->postcode)->toBe('BB2 4NY')
        ->and($job->lead->type)->toBe(LeadType::Booking)
        ->and($job->lead->status)->toBe(LeadStatus::Booked)
        ->and($job->lead->is_partial)->toBeFalse()
        ->and($job->lead->estimated_value_pence)->toBe(22300)
        ->and($job->lead->event_id)->toBeString();

    $wizard->assertRedirectContains('/book-repair/thank-you?ref='.$job->reference);

    Notification::assertSentTo($job->customer, BookingConfirmed::class);
    Notification::assertSentTo(new AnonymousNotifiable, StaffAlert::class);
});

it('keeps the booking as a lead once a mobile number is given, then completes the same lead', function () {
    $wizard = Livewire::test(BookingWizard::class)
        ->set('postcode', 'BB2 4NY')->call('checkPostcode')
        ->call('chooseModel', 'iphone-15')->call('submitDevice')
        ->set('selectedRepairs', [(string) $this->battery->id])->call('submitRepairs')
        ->set('slot', $this->slotKey)->call('submitCollection')
        ->set('phone', '07912 345678');

    $lead = Lead::query()->sole();

    expect($lead->is_partial)->toBeTrue()
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->phone)->toBe('+447912345678')
        ->and($lead->deviceModel->is($this->model))->toBeTrue()
        ->and($lead->details['repairs'])->toBe(['Battery Replacement'])
        ->and($lead->estimated_value_pence)->toBe(7400);

    $this->travel(10)->seconds();
    $wizard->set('firstName', 'Sarah')->set('lastName', 'Ahmed')->set('email', 'sarah@example.com')
        ->set('line1', '12 King Street')->set('town', 'Blackburn')->set('acceptTerms', true)
        ->call('submitDetails')
        ->call('confirm')
        ->assertHasNoErrors();

    expect(Lead::query()->count())->toBe(1)
        ->and($lead->fresh()->is_partial)->toBeFalse()
        ->and($lead->fresh()->repairJob)->not->toBeNull();
});

it('reuses an existing customer matched by mobile number', function () {
    $customer = Customer::factory()->create(['phone' => '+447912345678', 'email' => null]);

    $wizard = bookingAtReview();
    $this->travel(10)->seconds();
    $wizard->call('confirm')->assertHasNoErrors();

    expect(Customer::query()->count())->toBe(1)
        ->and(RepairJob::query()->sole()->customer->is($customer))->toBeTrue()
        ->and($customer->fresh()->email)->toBe('sarah@example.com');
});

it('opens prefilled from a price or postcode link', function () {
    Livewire::withQueryParams(['model' => 'iphone-15', 'repair' => 'screen-replacement', 'quality' => 'premium', 'postcode' => 'BB2 4NY'])
        ->test(BookingWizard::class)
        ->assertSet('step', 4)
        ->assertSet('model', 'iphone-15')
        ->assertSet('selectedRepairs', [(string) $this->screen->id])
        ->assertSet('qualities', [$this->screen->id => (string) $this->premium->id])
        ->assertSee('Screen Replacement (Premium)');
});

it('asks for the full postcode when only a district is given', function () {
    Livewire::test(BookingWizard::class)
        ->set('postcode', 'BB2')
        ->call('checkPostcode')
        ->assertHasErrors('postcode')
        ->assertSee('Please enter your full postcode')
        ->assertSet('step', 1);
});

it('captures an out-of-area visitor as a lead', function (bool $postIn, LeadType $type) {
    $settings = app(BookingSettings::class);
    $settings->post_in_enabled = $postIn;
    $settings->save();

    $wizard = Livewire::test(BookingWizard::class)
        ->set('postcode', 'PR1 2HE')
        ->call('checkPostcode')
        ->assertSet('outOfArea', true)
        ->assertSee('Sorry, we don’t collect from PR1 2HE yet.');

    $this->travel(10)->seconds();

    $wizard->set('outOfAreaName', 'Tom Hughes')
        ->set('outOfAreaPhone', '07912 345679')
        ->set('outOfAreaMessage', 'Cracked screen')
        ->call('requestOutOfAreaHelp')
        ->assertHasNoErrors()
        ->assertSee('Thanks, we will be in touch.');

    $lead = Lead::query()->sole();

    expect($lead->type)->toBe($type)
        ->and($lead->postcode)->toBe('PR1 2HE')
        ->and($lead->customer)->not->toBeNull();
})->with([
    'collection only' => [false, LeadType::OutOfArea],
    'post-in offered' => [true, LeadType::PostIn],
]);

it('books a diagnosis when the customer is not sure what is wrong', function () {
    $diagnostics = RepairType::factory()->create(['name' => 'Diagnostics', 'slug' => RepairType::DIAGNOSTICS, 'sort' => 99]);
    RepairPrice::factory()->for($this->model)->for($diagnostics)->create(['price_pence' => 0]);

    Livewire::test(BookingWizard::class)
        ->set('postcode', 'BB2 4NY')->call('checkPostcode')
        ->call('chooseModel', 'iphone-15')->call('submitDevice')
        ->assertSee('Not sure what’s wrong?')
        ->call('chooseDiagnostics')
        ->assertSet('step', 4)
        ->assertSet('selectedRepairs', [(string) $diagnostics->id]);
});

it('never offers a full collection window and refuses it if taken meanwhile', function () {
    RepairJob::factory()->count(2)->create([
        'collection_date' => '2026-11-30',
        'availability_window_id' => $this->morning->id,
        'status' => JobStatus::Booked,
    ]);

    bookingAtReviewWithoutSlot()
        ->set('slot', $this->slotKey)
        ->call('submitCollection')
        ->assertHasErrors('slot')
        ->assertSet('step', 4);
});

it('sends the customer back to choose another slot if theirs fills before they confirm', function () {
    $wizard = bookingAtReview();

    RepairJob::factory()->count(2)->create([
        'collection_date' => '2026-11-30',
        'availability_window_id' => $this->morning->id,
        'status' => JobStatus::Booked,
    ]);

    $this->travel(10)->seconds();
    $wizard->call('confirm')
        ->assertHasErrors('slot')
        ->assertSet('step', 4)
        ->assertSet('slot', '');

    expect(RepairJob::query()->whereNotNull('lead_id')->count())->toBe(0);
});

it('does not let steps be skipped', function () {
    Livewire::test(BookingWizard::class)
        ->call('submitDetails')
        ->assertHasErrors('form')
        ->assertSet('step', 1);

    Livewire::test(BookingWizard::class)
        ->set('postcode', 'BB2 4NY')->call('checkPostcode')
        ->call('goToStep', 6)
        ->assertSet('step', 2);
});

it('requires the repair terms to be accepted', function () {
    Livewire::test(BookingWizard::class)
        ->set('postcode', 'BB2 4NY')->call('checkPostcode')
        ->call('chooseModel', 'iphone-15')->call('submitDevice')
        ->set('selectedRepairs', [(string) $this->battery->id])->call('submitRepairs')
        ->set('slot', $this->slotKey)->call('submitCollection')
        ->set('firstName', 'Sarah')->set('lastName', 'Ahmed')->set('phone', '07912 345678')
        ->set('email', 'sarah@example.com')->set('line1', '12 King Street')->set('town', 'Blackburn')
        ->call('submitDetails')
        ->assertHasErrors(['acceptTerms' => 'accepted'])
        ->assertSet('step', 5);
});

it('rejects bots that fill in the hidden field', function () {
    $this->travel(10)->seconds();

    bookingAtReview()
        ->set('website', 'http://spam.example')
        ->call('confirm')
        ->assertHasErrors('confirm');

    expect(RepairJob::query()->count())->toBe(0);
});

it('stores where the visitor came from on the booking lead', function () {
    $this->get('/?gclid=TEST123&utm_source=google&utm_medium=cpc&utm_campaign=test')->assertOk();

    $wizard = bookingAtReview();
    $this->travel(10)->seconds();
    $wizard->call('confirm')->assertHasNoErrors();

    $lead = Lead::query()->sole();

    expect($lead->channel)->toBe(Channel::GoogleAds)
        ->and($lead->gclid)->toBe('TEST123')
        ->and($lead->utm_campaign)->toBe('test')
        ->and($lead->landing_page)->toBe('/?gclid=TEST123&utm_source=google&utm_medium=cpc&utm_campaign=test');
});

/**
 * Walks the wizard to the collection step.
 */
function bookingAtReviewWithoutSlot(): Testable
{
    $test = test();

    return Livewire::test(BookingWizard::class)
        ->set('postcode', 'BB2 4NY')->call('checkPostcode')
        ->call('chooseModel', 'iphone-15')->call('submitDevice')
        ->set('selectedRepairs', [(string) $test->battery->id])->call('submitRepairs')
        ->assertSet('step', 4)
        ->assertDontSeeHtml('value="'.$test->slotKey.'"')
        ->assertSeeHtml('value="2026-12-07:'.$test->morning->id.'"');
}

it('moves straight to the repairs as soon as a model is picked', function () {
    DeviceModel::factory()->create(['name' => 'iPhone 13', 'slug' => 'iphone-13', 'is_popular' => true]);
    RepairPrice::factory()->for(DeviceModel::query()->where('slug', 'iphone-13')->sole())->for($this->battery)->create();

    Livewire::test(BookingWizard::class)
        ->set('postcode', 'BB2 4NY')
        ->call('checkPostcode')
        ->assertSet('step', 2)
        ->assertSee('Search for your model')
        ->assertSee('Popular models')
        ->assertSee('Or choose from all models')
        ->call('chooseModel', 'iphone-15')
        ->assertSet('step', 3)
        ->assertSee('What needs fixing on your iPhone 15?');
});
