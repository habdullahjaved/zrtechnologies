<?php

namespace App\Services\Tracking;

use App\Settings\ConsentSettings;
use Illuminate\Http\Request;

/**
 * The visitor's cookie choice, read from the "zr_consent" cookie.
 *
 * The cookie is first-party, not encrypted (the browser script reads it before
 * any tag loads) and holds only the choice, e.g. "v=2026-11&a=1&m=0&id={uuid}".
 * A choice made under an older policy version counts as no choice.
 */
final readonly class ConsentState
{
    public const string COOKIE = 'zr_consent';

    /** How long a choice is remembered. The ICO suggests asking again at least every year. */
    public const int LIFETIME_DAYS = 180;

    public function __construct(
        public bool $decided = false,
        public bool $analytics = false,
        public bool $marketing = false,
        public ?string $consentId = null,
        public ?string $version = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = $request->cookies->get(self::COOKIE);

        if (! is_string($raw) || $raw === '') {
            return new self;
        }

        parse_str($raw, $values);

        $version = is_string($values['v'] ?? null) ? $values['v'] : null;
        $id = is_string($values['id'] ?? null) && preg_match('/^[0-9a-f-]{36}$/i', $values['id']) === 1 ? $values['id'] : null;

        if ($version === null || $version !== app(ConsentSettings::class)->policy_version) {
            return new self(consentId: $id);
        }

        return new self(
            decided: true,
            analytics: ($values['a'] ?? '0') === '1',
            marketing: ($values['m'] ?? '0') === '1',
            consentId: $id,
            version: $version,
        );
    }

    public function cookieValue(): string
    {
        return http_build_query([
            'v' => $this->version,
            'a' => $this->analytics ? '1' : '0',
            'm' => $this->marketing ? '1' : '0',
            'id' => $this->consentId,
        ]);
    }

    /**
     * Stored on each lead, so later server sends respect the choice made then.
     *
     * @return array{analytics: bool, marketing: bool, decided: bool, version: string|null, consent_id: string|null}
     */
    public function toArray(): array
    {
        return [
            'analytics' => $this->analytics,
            'marketing' => $this->marketing,
            'decided' => $this->decided,
            'version' => $this->version,
            'consent_id' => $this->consentId,
        ];
    }
}
