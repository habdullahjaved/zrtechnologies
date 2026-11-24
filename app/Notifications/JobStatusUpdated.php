<?php

namespace App\Notifications;

use App\Models\JobStatusChange;
use App\Models\RepairJob;
use App\Support\Messaging\MessageRenderer;
use App\Support\Messaging\MessageTemplateCatalogue;
use App\Support\Messaging\MessageVariables;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the customer their repair has moved on (collected, ready, returned…).
 */
class JobStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RepairJob $job, public JobStatusChange $change)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return app(MessageRenderer::class)->template($this->templateKey()) !== null ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return TemplatedMail::make(
            $this->templateKey(),
            app(MessageVariables::class)->forStatusChange($this->job, $this->change),
            'Track your repair',
            MessageVariables::trackUrl($this->job),
        ) ?? new MailMessage;
    }

    private function templateKey(): string
    {
        return MessageTemplateCatalogue::jobStatusKey($this->change->to_status);
    }
}
