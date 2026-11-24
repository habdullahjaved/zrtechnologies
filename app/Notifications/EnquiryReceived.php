<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Support\Messaging\MessageRenderer;
use App\Support\Messaging\MessageTemplateCatalogue;
use App\Support\Messaging\MessageVariables;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "We have your message" email after a quote, callback, contact or business enquiry.
 */
class EnquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return app(MessageRenderer::class)->template(MessageTemplateCatalogue::ENQUIRY_RECEIVED) !== null ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return TemplatedMail::make(
            MessageTemplateCatalogue::ENQUIRY_RECEIVED,
            app(MessageVariables::class)->forLead($this->lead),
        ) ?? new MailMessage;
    }
}
