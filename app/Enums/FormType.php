<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * How customers ask for a service in a category.
 */
enum FormType: string implements HasDescription, HasLabel
{
    case Booking = 'booking';
    case Quote = 'quote';
    case Enquiry = 'enquiry';

    public function getLabel(): string
    {
        return match ($this) {
            self::Booking => 'Booking wizard',
            self::Quote => 'Quote request',
            self::Enquiry => 'Enquiry form',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Booking => 'Customers pick a device, repair and collection slot and book straight away.',
            self::Quote => 'Customers describe what they need and receive a quote.',
            self::Enquiry => 'A simple contact form for services that need a conversation first.',
        };
    }
}
