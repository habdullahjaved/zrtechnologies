<?php

namespace App\Support;

use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

/**
 * UK phone helpers. Numbers are stored in E.164 (+447360127538) and shown in
 * national format (07360 127538).
 */
final class Phone
{
    public const string COUNTRY = 'GB';

    /**
     * Normalise any UK input ("07360 127538", "+44 7360 127538") to E.164.
     * Returns the trimmed input unchanged when it cannot be parsed.
     */
    public static function toE164(string $number): string
    {
        try {
            return (new PhoneNumber($number, self::COUNTRY))->formatE164();
        } catch (Throwable) {
            return trim($number);
        }
    }

    /**
     * Human-friendly UK national format, e.g. "07360 127538".
     */
    public static function display(string $number): string
    {
        try {
            return (new PhoneNumber($number, self::COUNTRY))->formatNational();
        } catch (Throwable) {
            return $number;
        }
    }

    /**
     * A tel: link target, e.g. "tel:+447360127538".
     */
    public static function telHref(string $number): string
    {
        return 'tel:'.self::toE164($number);
    }

    /**
     * A WhatsApp click-to-chat URL, optionally with a prefilled message.
     */
    public static function whatsAppUrl(string $number, ?string $message = null): string
    {
        $url = 'https://wa.me/'.ltrim(self::toE164($number), '+');

        return filled($message) ? $url.'?text='.rawurlencode($message) : $url;
    }
}
