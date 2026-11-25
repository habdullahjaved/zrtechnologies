<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use App\Notifications\BookingConfirmed;
use App\Notifications\StaffAlert;
use App\Settings\NotificationSettings;
use App\Support\Messaging\MessageTemplateCatalogue;
use App\Support\Messaging\MessageVariables;
use Illuminate\Support\Facades\Notification;

/**
 * Confirmation email to the customer and an alert to the team (both queued).
 */
class SendBookingNotifications
{
    public function __construct(
        private readonly NotificationSettings $settings,
        private readonly MessageVariables $variables,
    ) {}

    public function handle(BookingCreated $event): void
    {
        $job = $event->job->loadMissing(['customer', 'lead']);

        $this->sendConfirmation($job);

        if ($this->settings->notify_new_bookings) {
            Notification::route('mail', $this->settings->staff_emails)->notify(new StaffAlert(
                MessageTemplateCatalogue::STAFF_NEW_BOOKING,
                $this->variables->forJob($job),
                RepairJobResource::getUrl('view', ['record' => $job], panel: 'admin'),
                'Open job',
            ));
        }
    }

    /**
     * Sends the confirmation to the email typed into this booking. A repeat
     * customer found by mobile keeps the email already on file, so that is
     * only used when the booking itself has none.
     */
    private function sendConfirmation(RepairJob $job): void
    {
        $bookingEmail = $job->lead?->email;

        if (blank($bookingEmail) || mb_strtolower($bookingEmail) === mb_strtolower((string) $job->customer->email)) {
            if (filled($job->customer->email)) {
                $job->customer->notify(new BookingConfirmed($job));
            }

            return;
        }

        Notification::route('mail', $bookingEmail)->notify(new BookingConfirmed($job));
    }
}
