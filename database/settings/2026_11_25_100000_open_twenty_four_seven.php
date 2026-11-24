<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Open 24 hours, 7 days a week (owner's decision, 25/11/2026): calls,
 * WhatsApp and online bookings at any time. Collections and returns still
 * happen in the booking time windows. Keep Google Business Profile the same.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $allDay = ['is_open' => true, 'opens' => '00:00', 'closes' => '23:59'];

        $this->migrator->update('opening_hours.days', fn (): array => [
            'monday' => $allDay,
            'tuesday' => $allDay,
            'wednesday' => $allDay,
            'thursday' => $allDay,
            'friday' => $allDay,
            'saturday' => $allDay,
            'sunday' => $allDay,
        ]);

        $this->migrator->update(
            'opening_hours.note',
            fn (): string => 'Call, WhatsApp or book online any time, day or night. Collections and returns happen in the time windows you choose when you book.',
        );
    }
};
