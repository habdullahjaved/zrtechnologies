<?php

namespace App\Notifications\Channels;

use App\Notifications\StaffAlert;
use App\Settings\NotificationSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Posts team alerts to Slack (incoming webhook) and/or Telegram (bot API),
 * whichever are set up in Settings → Notifications. A failing chat app never
 * stops the email alert or the booking.
 */
class StaffWebhookChannel
{
    public function __construct(private readonly NotificationSettings $settings) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof StaffAlert) {
            return;
        }

        $message = $notification->toWebhook();
        $text = $message['title']."\n\n".$message['text']."\n\n".$message['url'];

        if (filled($this->settings->slack_webhook_url)) {
            $this->post((string) $this->settings->slack_webhook_url, ['text' => $text], 'Slack');
        }

        if ($this->settings->telegramConfigured()) {
            $this->post(
                'https://api.telegram.org/bot'.$this->settings->telegram_bot_token.'/sendMessage',
                ['chat_id' => $this->settings->telegram_chat_id, 'text' => $text, 'disable_web_page_preview' => true],
                'Telegram',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $url, array $payload, string $service): void
    {
        $response = Http::timeout(5)->retry(2, 500, throw: false)->asJson()->post($url, $payload);

        if ($response->failed()) {
            Log::warning("{$service} staff alert failed", ['status' => $response->status()]);
        }
    }
}
