<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * What happened to a server-side conversion.
 */
enum TrackingStatus: string implements HasColor, HasLabel
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    /** The visitor did not agree to the cookies this platform needs. */
    case SkippedNoConsent = 'skipped_no_consent';
    /** The platform's IDs are not filled in yet, or nothing identifies the visitor. */
    case Skipped = 'skipped';
    /** Waiting in the offline-conversions CSV for Google Ads to collect. */
    case Exported = 'exported';

    public function getLabel(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::SkippedNoConsent => 'Not sent: no consent',
            self::Skipped => 'Not sent',
            self::Exported => 'In CSV export',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Sent, self::Exported => 'success',
            self::Failed => 'danger',
            self::Queued => 'info',
            default => 'gray',
        };
    }
}
