<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Phase 3: booking wording and who is told about new bookings and enquiries.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('booking.reference_prefix', 'ZR');
        $this->migrator->add('booking.payment_note', 'Nothing to pay today. You pay when your iPhone is returned, by card or bank transfer. If the price changes after diagnosis, we ask you first.');
        $this->migrator->add('booking.passcode_notice', 'We never ask for your passcode in this form. Please back up your iPhone before we collect it. Keep Find My switched on: we only ask you to switch it off if a repair needs it, and we will explain why.');

        $this->migrator->add('notifications.staff_emails', [config('zr.super_admin.email')]);
        $this->migrator->add('notifications.notify_new_bookings', true);
        $this->migrator->add('notifications.notify_new_enquiries', true);
        $this->migrator->add('notifications.notify_partial_bookings', false);
        $this->migrator->add('notifications.slack_webhook_url', null);
        $this->migrator->addEncrypted('notifications.telegram_bot_token', null);
        $this->migrator->add('notifications.telegram_chat_id', null);
    }
};
