<?php

namespace App\Support;

/**
 * Money is stored as integer pence everywhere and formatted only here.
 */
final class Money
{
    /**
     * Format pence as pounds: whole amounts drop the pence ("£59"), others keep them ("£59.99").
     */
    public static function format(int $pence): string
    {
        $pounds = $pence / 100;

        return $pence % 100 === 0
            ? '£'.number_format($pounds)
            : '£'.number_format($pounds, 2);
    }

    /**
     * Pence as a decimal pounds string for forms and CSV files, e.g. "59.99".
     */
    public static function toPounds(int $pence): string
    {
        return number_format($pence / 100, 2, '.', '');
    }

    /**
     * Parse a pounds amount typed by a person ("59", "59.9", "£1,059.99") into pence.
     * Returns null when the value is not a valid non-negative amount.
     */
    public static function parsePounds(string|int|float|null $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalised = str_replace(['£', ',', ' '], '', trim((string) $value));

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $normalised)) {
            return null;
        }

        return (int) round(((float) $normalised) * 100);
    }
}
