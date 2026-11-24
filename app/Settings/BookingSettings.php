<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Rules for which collection slots customers may book.
 */
class BookingSettings extends Settings
{
    /** Minimum notice before a collection window starts. */
    public int $lead_time_hours;

    /** Same-day collections can only be booked before this time (HH:MM, UK time). */
    public string $same_day_cutoff;

    /** How far ahead customers may book. */
    public int $max_days_ahead;

    /** Offer a post-in repair to customers outside the collection area. */
    public bool $post_in_enabled;

    /** Start of every job reference, e.g. "ZR" gives ZR-2026-000123. */
    public string $reference_prefix;

    /** Shown on the booking summary, thank-you page and confirmation email. */
    public string $payment_note;

    /** Passcode, backup and Find My guidance shown before the customer confirms. */
    public string $passcode_notice;

    public static function group(): string
    {
        return 'booking';
    }
}
