<?php

namespace App\Listeners;

use App\Enums\JobStatus;
use App\Events\BookingCreated;
use App\Events\JobStatusChanged;
use App\Events\LeadCaptured;
use App\Services\Tracking\ConversionDispatcher;

/**
 * Server-side conversions: website leads and bookings as they arrive, and
 * the offline conversion when a repair is completed.
 *
 * Runs straight after the request's database transaction commits (the sends
 * themselves are queued), so the visitor's IP address can be passed to Meta
 * without ever being stored.
 */
class SendServerConversions
{
    public function __construct(private readonly ConversionDispatcher $dispatcher) {}

    public function handleLead(LeadCaptured $event): void
    {
        $this->dispatcher->lead($event->lead, $this->ipAddress());
    }

    public function handleBooking(BookingCreated $event): void
    {
        $lead = $event->job->loadMissing('lead')->lead;

        if ($lead !== null) {
            $this->dispatcher->lead($lead, $this->ipAddress());
        }
    }

    public function handleStatusChanged(JobStatusChanged $event): void
    {
        if ($event->change->to_status === JobStatus::Completed) {
            $this->dispatcher->completedJob($event->job);
        }
    }

    private function ipAddress(): ?string
    {
        return app()->runningInConsole() ? null : request()->ip();
    }
}
