<?php

namespace App\Listeners;

use App\Events\JobStatusChanged;
use App\Notifications\JobStatusUpdated;

/**
 * Emails the customer about a status change when staff chose to notify them.
 */
class NotifyCustomerOfJobStatus
{
    public function handle(JobStatusChanged $event): void
    {
        if (! $event->change->customer_notified) {
            return;
        }

        $job = $event->job->loadMissing('customer');

        if (filled($job->customer->email)) {
            $job->customer->notify(new JobStatusUpdated($job, $event->change));
        }
    }
}
