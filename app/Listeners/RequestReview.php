<?php

namespace App\Listeners;

use App\Enums\JobStatus;
use App\Events\JobStatusChanged;
use App\Notifications\ReviewRequest;
use App\Settings\NotificationSettings;
use App\Settings\SocialSettings;

/**
 * When a repair is completed, queues one review request email for a few
 * hours later, if review requests are on and a Google review link is set.
 */
class RequestReview
{
    /** Give the customer time to use their phone before asking. */
    public const int DELAY_HOURS = 3;

    public function __construct(
        private readonly NotificationSettings $notifications,
        private readonly SocialSettings $social,
    ) {}

    public function handle(JobStatusChanged $event): void
    {
        $job = $event->job->loadMissing('customer');

        if ($event->change->to_status !== JobStatus::Completed
            || ! $this->notifications->send_review_requests
            || blank($this->social->google_review_url)
            || $job->review_requested_at !== null
            || blank($job->customer->email)) {
            return;
        }

        $job->customer->notify((new ReviewRequest($job))->delay(now()->addHours(self::DELAY_HOURS)));
        $job->forceFill(['review_requested_at' => now()])->save();
    }
}
