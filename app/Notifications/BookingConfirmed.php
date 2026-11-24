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
 * The customer's booking confirmation email.
 */
class BookingConfirmed extends Notification implements ShouldQueue
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
        return app(MessageRenderer::class)->template(MessageTemplateCatalogue::BOOKING_CONFIRMATION) !== null ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return TemplatedMail::make(
            MessageTemplateCatalogue::BOOKING_CONFIRMATION,
            app(MessageVariables::class)->forJob($this->job),
            'Track your repair',
            MessageVariables::trackUrl($this->job),
        ) ?? new MailMessage;
    }
}
