<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * How a lead reached us.
 */
enum LeadType: string implements HasColor, HasIcon, HasLabel
{
    case Booking = 'booking';
    case Quote = 'quote';
    case Callback = 'callback';
    case Contact = 'contact';
    case Business = 'business';
    case OutOfArea = 'out_of_area';
    case PostIn = 'post_in';
    case WhatsAppClick = 'whatsapp_click';
    case CallClick = 'call_click';
    case Chatbot = 'chatbot';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Booking => 'Online booking',
            self::Quote => 'Quote request',
            self::Callback => 'Callback request',
            self::Contact => 'Contact form',
            self::Business => 'Business enquiry',
            self::OutOfArea => 'Outside our area',
            self::PostIn => 'Post-in repair request',
            self::WhatsAppClick => 'WhatsApp',
            self::CallClick => 'Phone call',
            self::Chatbot => 'Chatbot',
            self::Manual => 'Added by staff',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Booking => 'success',
            self::Quote, self::Business => 'info',
            self::Callback, self::CallClick => 'warning',
            self::OutOfArea, self::PostIn => 'gray',
            default => 'primary',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Booking => Heroicon::OutlinedCalendarDays,
            self::Quote => Heroicon::OutlinedCurrencyPound,
            self::Callback, self::CallClick => Heroicon::OutlinedPhone,
            self::Contact => Heroicon::OutlinedEnvelope,
            self::Business => Heroicon::OutlinedBuildingOffice2,
            self::OutOfArea, self::PostIn => Heroicon::OutlinedMapPin,
            self::WhatsAppClick, self::Chatbot => Heroicon::OutlinedChatBubbleLeftRight,
            self::Manual => Heroicon::OutlinedUserPlus,
        };
    }

    /**
     * Types staff can choose when logging an enquiry by hand.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        $options = [];

        foreach ([self::Manual, self::CallClick, self::WhatsAppClick, self::Quote, self::Business] as $type) {
            $options[$type->value] = $type->getLabel();
        }

        return $options;
    }
}
