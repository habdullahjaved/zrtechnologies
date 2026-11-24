<?php

namespace App\Support;

/**
 * UK postcode formatting and format checks (no network calls).
 */
final class Postcode
{
    private const string FULL = '/^([A-Z]{1,2}\d[A-Z\d]?)(\d[A-Z]{2})$/';

    private const string OUTCODE = '/^[A-Z]{1,2}\d[A-Z\d]?$/';

    /**
     * Upper-case a postcode and put the single space in the right place:
     * " bb24ny " → "BB2 4NY". Returns null when it is not a full UK postcode.
     */
    public static function normalise(?string $value): ?string
    {
        $compact = self::compact($value);

        if (! preg_match(self::FULL, $compact, $parts)) {
            return null;
        }

        return $parts[1].' '.$parts[2];
    }

    /**
     * The outward code (district), e.g. "BB2" from "BB2 4NY" or from "bb2" on its own.
     * Returns null when the value is neither a full postcode nor a district.
     */
    public static function outcode(?string $value): ?string
    {
        $compact = self::compact($value);

        if (preg_match(self::FULL, $compact, $parts)) {
            return $parts[1];
        }

        return preg_match(self::OUTCODE, $compact) ? $compact : null;
    }

    public static function isDistrictOnly(?string $value): bool
    {
        return self::normalise($value) === null && self::outcode($value) !== null;
    }

    private static function compact(?string $value): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', (string) $value));
    }
}
