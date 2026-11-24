<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Sections of the /faqs page, in display order.
 */
enum FaqTopic: string implements HasLabel
{
    case Booking = 'booking';
    case Repairs = 'repairs';
    case Prices = 'prices';
    case Warranty = 'warranty';
    case Data = 'data';
    case Business = 'business';

    public function getLabel(): string
    {
        return match ($this) {
            self::Booking => 'Booking, collection and return',
            self::Repairs => 'Repairs and parts',
            self::Prices => 'Prices and payment',
            self::Warranty => 'Warranty and aftercare',
            self::Data => 'Your data and privacy',
            self::Business => 'Business repairs',
        };
    }
}
