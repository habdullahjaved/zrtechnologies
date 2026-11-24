<?php

use App\Actions\Jobs\ChangeJobStatus;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\RepairJob;
use App\Models\RepairJobItem;
use App\Models\User;
use App\Notifications\JobStatusUpdated;
use App\Support\Messaging\MessageTemplateCatalogue;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow('2026-12-01 10:00');
    $this->seed(MessageTemplateSeeder::class);

    $this->customer = Customer::factory()->create(['first_name' => 'Sarah', 'email' => 'sarah@example.com']);
    $this->lead = Lead::factory()->status(LeadStatus::Booked)->create(['customer_id' => $this->customer->id]);
    $this->job = RepairJob::factory()->create([
        'customer_id' => $this->customer->id,
        'lead_id' => $this->lead->id,
        'collection_address_id' => Address::factory()->for($this->customer)->create()->id,
        'quoted_price_pence' => 8900,
        'collection_fee_pence' => 500,
    ]);
    RepairJobItem::factory()->for($this->job)->create(['warranty_days' => 90]);
    RepairJobItem::factory()->for($this->job)->create(['warranty_days' => 365]);
    $this->user = User::factory()->create();
    $this->changer = app(ChangeJobStatus::class);
});

afterEach(fn () => Carbon::setTestNow());

it('moves a job on, records who did it and emails the customer', function () {
    $change = $this->changer->handle($this->job, JobStatus::Collected, $this->user, 'Screen badly cracked', 'Your iPhone is on its way to the workshop.');

    $this->job->refresh();

    expect($this->job->status)->toBe(JobStatus::Collected)
        ->and($this->job->collected_at->toDateTimeString())->toBe('2026-12-01 10:00:00')
        ->and($change->from_status)->toBe(JobStatus::Booked)
        ->and($change->user->is($this->user))->toBeTrue()
        ->and($change->note)->toBe('Screen badly cracked')
        ->and($change->customer_notified)->toBeTrue()
        ->and($this->lead->fresh()->status)->toBe(LeadStatus::Collected);

    Notification::assertSentTo($this->customer, JobStatusUpdated::class, function (JobStatusUpdated $notification): bool {
        $mail = $notification->toMail($this->customer);

        return $mail->subject === 'We have your iPhone: '.$this->job->reference
            && str_contains($mail->viewData['body'], 'Your iPhone is on its way to the workshop.');
    });
});

it('refuses moves that skip the workflow', function () {
    $this->changer->handle($this->job, JobStatus::Completed);
})->throws(ValidationException::class);

it('does not email when staff untick notify, or when the template is switched off', function () {
    $change = $this->changer->handle($this->job, JobStatus::CollectionScheduled, notifyCustomer: false);
    expect($change->customer_notified)->toBeFalse();

    MessageTemplate::query()->where('key', MessageTemplateCatalogue::jobStatusKey(JobStatus::Collected))->update(['is_active' => false]);
    $change = $this->changer->handle($this->job->fresh(), JobStatus::Collected);
    expect($change->customer_notified)->toBeFalse();

    Notification::assertNothingSent();
});

it('completes a job: final price, payment, warranty and the lead marked won', function () {
    $job = $this->job;

    foreach ([JobStatus::Collected, JobStatus::Diagnosing, JobStatus::Repairing, JobStatus::QualityCheck, JobStatus::ReadyForReturn, JobStatus::Returned] as $status) {
        $this->changer->handle($job, $status, notifyCustomer: false);
        $job->refresh();
    }

    $this->changer->handle($job, JobStatus::Completed);
    $job->refresh();

    expect($job->final_price_pence)->toBe(9400)
        ->and($job->paid_at)->not->toBeNull()
        ->and($job->diagnosed_at)->not->toBeNull()
        ->and($job->warranty_expires_at->toDateString())->toBe('2027-12-01')
        ->and($job->statusHistory()->count())->toBe(7)
        ->and($this->lead->fresh()->status)->toBe(LeadStatus::Completed)
        ->and($this->lead->fresh()->final_value_pence)->toBe(9400);
});

it('marks the lead lost when a booking is cancelled', function () {
    $this->changer->handle($this->job, JobStatus::Cancelled, customerMessage: 'Cancelled at your request.');

    expect($this->job->fresh()->cancelled_at)->not->toBeNull()
        ->and($this->lead->fresh()->status)->toBe(LeadStatus::Lost)
        ->and($this->lead->fresh()->lost_reason)->toBe('Booking cancelled');
});

it('only offers sensible next steps', function () {
    expect(JobStatus::Booked->nextStatuses())->toContain(JobStatus::Cancelled)
        ->and(JobStatus::Collected->canMoveTo(JobStatus::Cancelled))->toBeFalse()
        ->and(JobStatus::Completed->nextStatuses())->toBe([]);
});
