<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Filament\Resources\RepairJobs\RepairJobResource;
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
        $job = $event->job->loadMissing('customer');

        if (filled($job->customer->email)) {
            $job->customer->notify(new BookingConfirmed($job));
        }

        if ($this->settings->notify_new_bookings) {
            Notification::route('mail', $this->settings->staff_emails)->notify(new StaffAlert(
                MessageTemplateCatalogue::STAFF_NEW_BOOKING,
                $this->variables->forJob($job),
                RepairJobResource::getUrl('view', ['record' => $job], panel: 'admin'),
                'Open job',
            ));
        }
    }
}
