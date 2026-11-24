<?php

use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Livewire\EnquiryForm;
use App\Models\Customer;
use App\Models\DeviceModel;
use App\Models\Lead;
use App\Models\RepairPrice;
use App\Notifications\EnquiryReceived;
use App\Notifications\StaffAlert;
use App\Settings\NotificationSettings;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    RateLimiter::clear('enquiry:127.0.0.1');
});

it('saves a contact message as a lead and emails both sides', function () {
    $form = Livewire::test(EnquiryForm::class, ['type' => 'contact']);
    $this->travel(5)->seconds();

    $form->set('name', 'Sarah Ahmed')
        ->set('email', 'sarah@example.com')
        ->set('message', 'Do you repair iPads too?')
        ->set('marketingConsent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Thanks, we have your message.');

    $lead = Lead::query()->sole();

    expect($lead->reference)->toMatch('/^ENQ-\d{4}-\d{6}$/')
        ->and($lead->type)->toBe(LeadType::Contact)
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->channel)->toBe(Channel::Direct)
        ->and($lead->marketing_consent)->toBeTrue()
        ->and($lead->customer->first_name)->toBe('Sarah')
        ->and($lead->customer->last_name)->toBe('Ahmed')
        ->and($lead->customer->marketing_consent)->toBeTrue()
        ->and($lead->customer->marketing_consent_source)->toBe('contact_form');

    $form->assertSee($lead->reference);

    Notification::assertSentTo(new AnonymousNotifiable, EnquiryReceived::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'sarah@example.com');
    Notification::assertSentTo(new AnonymousNotifiable, StaffAlert::class);
});

it('takes a callback request with the best time to call', function () {
    $form = Livewire::test(EnquiryForm::class, ['type' => 'callback']);
    $this->travel(5)->seconds();

    $form->set('name', 'Tom')
        ->set('phone', '07912 345678')
        ->set('callbackTime', 'evening')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Thanks, we will call you back.');

    $lead = Lead::query()->sole();

    expect($lead->type)->toBe(LeadType::Callback)
        ->and($lead->phone)->toBe('+447912345678')
        ->and($lead->details['callback_time'])->toBe('Early evening (5pm–7pm)');

    Notification::assertNotSentTo(new AnonymousNotifiable, EnquiryReceived::class);
});

it('takes a quote request for a model and repair', function () {
    $model = DeviceModel::factory()->create(['name' => 'iPhone 13', 'slug' => 'iphone-13']);
    $price = RepairPrice::factory()->for($model)->create();

    $form = Livewire::test(EnquiryForm::class, ['type' => 'quote']);
    $this->travel(5)->seconds();

    $form->set('name', 'Priya')
        ->set('phone', '07912 345678')
        ->set('model', 'iphone-13')
        ->set('repair', $price->repairType->slug)
        ->set('postcode', 'bb24ny')
        ->call('submit')
        ->assertHasNoErrors();

    $lead = Lead::query()->sole();

    expect($lead->type)->toBe(LeadType::Quote)
        ->and($lead->deviceModel->is($model))->toBeTrue()
        ->and($lead->repair_type_id)->toBe($price->repair_type_id)
        ->and($lead->postcode)->toBe('BB2 4NY');
});

it('takes a business enquiry', function () {
    $form = Livewire::test(EnquiryForm::class, ['type' => 'business']);
    $this->travel(5)->seconds();

    $form->set('companyName', 'Blackburn College')
        ->set('name', 'Jane Smith')
        ->set('email', 'it@example.ac.uk')
        ->set('fleetSize', '21-50')
        ->set('message', 'We have 30 iPhones with cracked screens.')
        ->call('submit')
        ->assertHasNoErrors();

    $lead = Lead::query()->sole();

    expect($lead->type)->toBe(LeadType::Business)
        ->and($lead->company_name)->toBe('Blackburn College')
        ->and($lead->details['fleet_size'])->toBe('21 to 50 devices');
});

it('links repeat enquiries to the same customer', function () {
    $customer = Customer::factory()->create(['email' => 'sarah@example.com']);

    $form = Livewire::test(EnquiryForm::class, ['type' => 'contact']);
    $this->travel(5)->seconds();

    $form->set('name', 'Sarah Ahmed')->set('email', 'SARAH@example.com')->set('message', 'Hello')->call('submit');

    expect(Customer::query()->count())->toBe(1)
        ->and(Lead::query()->sole()->customer->is($customer))->toBeTrue();
});

it('validates each form', function () {
    Livewire::test(EnquiryForm::class, ['type' => 'contact'])
        ->call('submit')
        ->assertHasErrors(['name' => 'required', 'email' => 'required', 'message' => 'required']);

    Livewire::test(EnquiryForm::class, ['type' => 'callback'])
        ->set('name', 'Tom')
        ->set('phone', '12345')
        ->call('submit')
        ->assertHasErrors(['phone']);

    Livewire::test(EnquiryForm::class, ['type' => 'quote'])
        ->set('name', 'Tom')
        ->call('submit')
        ->assertHasErrors(['phone' => 'required_without', 'model' => 'required']);
});

it('stops bots: the hidden field, sending too fast and repeated sends', function () {
    Livewire::test(EnquiryForm::class, ['type' => 'contact'])
        ->set('name', 'Bot')->set('email', 'bot@example.com')->set('message', 'Spam')
        ->call('submit')
        ->assertHasErrors('form');

    $form = Livewire::test(EnquiryForm::class, ['type' => 'contact']);
    $this->travel(5)->seconds();
    $form->set('website', 'https://spam.example')->set('name', 'Bot')->set('email', 'bot@example.com')->set('message', 'Spam')
        ->call('submit')
        ->assertHasErrors('form');

    expect(Lead::query()->count())->toBe(0);

    foreach (range(1, 5) as $attempt) {
        $form = Livewire::test(EnquiryForm::class, ['type' => 'contact']);
        $this->travel(5)->seconds();
        $form->set('name', 'Sarah')->set('email', 'sarah@example.com')->set('message', 'Hello '.$attempt)->call('submit')->assertHasNoErrors();
    }

    $form = Livewire::test(EnquiryForm::class, ['type' => 'contact']);
    $this->travel(5)->seconds();
    $form->set('name', 'Sarah')->set('email', 'sarah@example.com')->set('message', 'Hello again')
        ->call('submit')
        ->assertHasErrors('form')
        ->assertSee('You have sent this a few times already');

    expect(Lead::query()->count())->toBe(5);
});

it('only alerts the team when that is switched on', function () {
    $settings = app(NotificationSettings::class);
    $settings->notify_new_enquiries = false;
    $settings->save();

    $form = Livewire::test(EnquiryForm::class, ['type' => 'contact']);
    $this->travel(5)->seconds();
    $form->set('name', 'Sarah')->set('email', 'sarah@example.com')->set('message', 'Hello')->call('submit');

    Notification::assertNotSentTo(new AnonymousNotifiable, StaffAlert::class);
    Notification::assertSentTo(new AnonymousNotifiable, EnquiryReceived::class);
});
