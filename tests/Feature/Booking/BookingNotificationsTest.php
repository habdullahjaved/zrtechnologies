<?php

use App\Events\BookingCreated;
use App\Listeners\SendBookingNotifications;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\RepairJob;
use App\Notifications\BookingConfirmed;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

function bookingFor(Customer $customer, ?string $bookingEmail): RepairJob
{
    $lead = Lead::factory()->create(['customer_id' => $customer->getKey(), 'email' => $bookingEmail]);

    return RepairJob::factory()->for($customer)->create(['lead_id' => $lead->getKey()]);
}

it('sends the confirmation to the email typed into the booking when a repeat customer has another on file', function () {
    $customer = Customer::factory()->create(['email' => 'old@example.com']);
    $job = bookingFor($customer, 'new@example.com');

    app(SendBookingNotifications::class)->handle(new BookingCreated($job));

    Notification::assertSentTo(
        new AnonymousNotifiable,
        BookingConfirmed::class,
        fn (BookingConfirmed $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'new@example.com',
    );
    Notification::assertNotSentTo($customer, BookingConfirmed::class);
});

it('sends the confirmation to the customer when the booking email matches', function () {
    $customer = Customer::factory()->create(['email' => 'sarah@example.com']);
    $job = bookingFor($customer, 'Sarah@Example.com');

    app(SendBookingNotifications::class)->handle(new BookingCreated($job));

    Notification::assertSentTo($customer, BookingConfirmed::class);
});

it('falls back to the customer email when the booking has none', function () {
    $customer = Customer::factory()->create(['email' => 'sarah@example.com']);
    $job = bookingFor($customer, null);

    app(SendBookingNotifications::class)->handle(new BookingCreated($job));

    Notification::assertSentTo($customer, BookingConfirmed::class);
});
