<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Starting opening hours. The owner confirms them in Admin → Settings → Opening hours
 * before launch, and they must match Google Business Profile.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $weekday = ['is_open' => true, 'opens' => '09:00', 'closes' => '19:00'];

        $this->migrator->add('opening_hours.days', [
            'monday' => $weekday,
            'tuesday' => $weekday,
            'wednesday' => $weekday,
            'thursday' => $weekday,
            'friday' => $weekday,
            'saturday' => ['is_open' => true, 'opens' => '10:00', 'closes' => '17:00'],
            'sunday' => ['is_open' => false, 'opens' => null, 'closes' => null],
        ]);

        $this->migrator->add('opening_hours.note', 'Collections and returns by appointment within these hours.');
    }
};
