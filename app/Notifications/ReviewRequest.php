<?php

namespace App\Notifications;

use App\Models\RepairJob;
use App\Support\Messaging\MessageRenderer;
use App\Support\Messaging\MessageTemplateCatalogue;
use App\Support\Messaging\MessageVariables;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Asks a customer whose repair is complete for a Google review. Sent once per
 * repair, a few hours after completion, to genuine customers only, with no
 * incentive (Google's review policy and the DMCC Act 2024).
 */
class ReviewRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RepairJob $job)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return app(MessageRenderer::class)->template(MessageTemplateCatalogue::REVIEW_REQUEST) !== null ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return TemplatedMail::make(
            MessageTemplateCatalogue::REVIEW_REQUEST,
            app(MessageVariables::class)->forJob($this->job),
            'Leave a Google review',
            route('reviews.leave'),
        ) ?? new MailMessage;
    }
}
