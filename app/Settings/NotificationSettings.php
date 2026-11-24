<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Who on the team hears about new bookings and enquiries, and where.
 */
class NotificationSettings extends Settings
{
    /** @var list<string> */
    public array $staff_emails;

    public bool $notify_new_bookings;

    public bool $notify_new_enquiries;

    /** Also alert when someone leaves the booking form after giving a phone number or email. */
    public bool $notify_partial_bookings;

    /** Slack "incoming webhook" URL. */
    public ?string $slack_webhook_url;

    public ?string $telegram_bot_token;

    public ?string $telegram_chat_id;

    public static function group(): string
    {
        return 'notifications';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['telegram_bot_token'];
    }

    public function telegramConfigured(): bool
    {
        return filled($this->telegram_bot_token) && filled($this->telegram_chat_id);
    }
}
