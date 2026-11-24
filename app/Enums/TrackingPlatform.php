<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TrackingPlatform: string implements HasLabel
{
    case Ga4 = 'ga4';
    case Meta = 'meta';
    case GoogleAds = 'google_ads';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ga4 => 'Google Analytics 4',
            self::Meta => 'Meta (Facebook / Instagram)',
            self::GoogleAds => 'Google Ads',
        };
    }
}
