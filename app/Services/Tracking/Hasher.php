<?php

namespace App\Services\Tracking;

use App\Support\Phone;
use Throwable;

/**
 * Normalises and SHA-256 hashes personal data for Meta's Conversions API and
 * Google's enhanced conversions, following their normalisation rules:
 * trimmed, lowercase, phone numbers as digits with the country code, and the
 * UK postcode without spaces. Plain values never leave the server.
 */
final class Hasher
{
    public static function hash(?string $normalised): ?string
    {
        return $normalised === null || $normalised === '' ? null : hash('sha256', $normalised);
    }

    public static function email(?string $email): ?string
    {
        return self::hash($email !== null ? mb_strtolower(trim($email)) : null);
    }

    /**
     * "07360 127538" or "+44 7360 127538" → hash of "447360127538".
     */
    public static function phone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        try {
            $e164 = Phone::toE164((string) $phone);
        } catch (Throwable) {
            return null;
        }

        // Phone::toE164() hands back unparseable input unchanged: never hash that.
        return preg_match('/^\+\d{8,15}$/', $e164) === 1 ? self::hash(ltrim($e164, '+')) : null;
    }

    /**
     * First or last name: lowercase, letters only (accents kept, punctuation removed).
     */
    public static function name(?string $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        return self::hash(preg_replace('/[^\p{L}]/u', '', mb_strtolower(trim((string) $name))));
    }

    public static function postcode(?string $postcode): ?string
    {
        return self::hash($postcode !== null ? mb_strtolower(preg_replace('/\s+/', '', $postcode) ?? '') : null);
    }

    public static function city(?string $city): ?string
    {
        return self::hash($city !== null ? mb_strtolower(preg_replace('/[^\p{L}]/u', '', $city) ?? '') : null);
    }
}
