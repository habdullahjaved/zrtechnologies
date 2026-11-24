<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a message template is used.
 */
enum MessageChannel: string implements HasColor, HasLabel
{
    /** Email to a customer. */
    case Mail = 'mail';

    /** Alert to the team (email, Slack, Telegram). */
    case Staff = 'staff';

    /** Prefilled WhatsApp message staff send with one click. */
    case WhatsApp = 'whatsapp';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mail => 'Customer email',
            self::Staff => 'Team alert',
            self::WhatsApp => 'WhatsApp message',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Mail => 'primary',
            self::Staff => 'warning',
            self::WhatsApp => 'success',
        };
    }
}
