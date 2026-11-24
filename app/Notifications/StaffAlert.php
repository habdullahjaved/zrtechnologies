<?php

namespace App\Notifications;

use App\Notifications\Channels\StaffWebhookChannel;
use App\Settings\NotificationSettings;
use App\Support\Messaging\MessageRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Tells the team about a new booking or enquiry by email and, when set up,
 * Slack and Telegram. Wording comes from the "Team alert" templates.
 */
class StaffAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string|HtmlString|null>  $variables
     */
    public function __construct(
        public string $templateKey,
        public array $variables,
        public string $adminUrl,
        public string $actionText = 'Open in admin',
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if (app(MessageRenderer::class)->template($this->templateKey) === null) {
            return [];
        }

        $settings = app(NotificationSettings::class);
        $channels = $settings->staff_emails !== [] ? ['mail'] : [];

        if (filled($settings->slack_webhook_url) || $settings->telegramConfigured()) {
            $channels[] = StaffWebhookChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return TemplatedMail::make($this->templateKey, [...$this->variables, 'admin_url' => $this->adminUrl], $this->actionText, $this->adminUrl)
            ?? new MailMessage;
    }

    /**
     * Plain text for chat apps.
     *
     * @return array{title: string, text: string, url: string}
     */
    public function toWebhook(): array
    {
        $renderer = app(MessageRenderer::class);
        $template = $renderer->template($this->templateKey);
        $variables = [...$this->variables, 'admin_url' => $this->adminUrl];

        return [
            'title' => $renderer->render((string) $template?->subject, $variables, escape: false),
            'text' => str_replace('**', '', $renderer->render((string) $template?->body, $variables, escape: false)),
            'url' => $this->adminUrl,
        ];
    }
}
