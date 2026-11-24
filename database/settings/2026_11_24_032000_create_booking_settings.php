<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Collection booking rules. Draft values for the owner to confirm.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('booking.lead_time_hours', 2);
        $this->migrator->add('booking.same_day_cutoff', '12:00');
        $this->migrator->add('booking.max_days_ahead', 21);
        $this->migrator->add('booking.post_in_enabled', false);
    }
};
