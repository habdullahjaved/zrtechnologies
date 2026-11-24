<?php

namespace App\Services\Attribution;

use App\Data\AttributionData;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Remembers where a visitor came from so it can be stored on their lead.
 *
 * Touches live in the visitor's session (first party, no extra cookie):
 *  - first touch: the first visit in this session;
 *  - last touch: the latest visit that brought click IDs, UTM tags or an
 *    outside referrer (plain direct visits never overwrite it);
 *  - the last few pages viewed, for the lead timeline.
 *
 * A 90-day cross-session cookie, set only with consent, follows with the
 * cookie banner in Phase 5.
 */
final class AttributionTracker
{
    public const string SESSION_KEY = 'attribution';

    /** Query parameters captured from landing URLs. */
    public const array PARAMETERS = [
        'gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'ttclid',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
    ];

    private const int MAX_PAGES = 15;

    public function __construct(private readonly ChannelResolver $channels) {}

    public function capture(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $session = $request->session();
        $state = (array) $session->get(self::SESSION_KEY, []);
        $touch = $this->touchFrom($request);
        $isCampaignTouch = $this->isCampaignTouch($touch);

        if (! isset($state['first_touch'])) {
            $state['first_touch'] = $touch;
        }

        if ($isCampaignTouch) {
            $state['last_touch'] = $touch;
        }

        $pages = (array) ($state['pages'] ?? []);
        $pages[] = ['path' => Str::limit('/'.ltrim($request->path(), '/'), 200, ''), 'at' => now()->toIso8601String()];
        $state['pages'] = array_slice($pages, -self::MAX_PAGES);

        $session->put(self::SESSION_KEY, $state);
    }

    /**
     * Everything known about this visitor's source, ready to store on a lead.
     */
    public function current(Request $request): AttributionData
    {
        $session = $this->session($request);
        $state = $session !== null ? (array) $session->get(self::SESSION_KEY, []) : [];

        /** @var array<string, string|null> $first */
        $first = (array) ($state['first_touch'] ?? []);

        /** @var array<string, string|null> $last */
        $last = (array) ($state['last_touch'] ?? $first);

        return new AttributionData(
            channel: $this->channels->resolve($last),
            firstTouchChannel: $first !== [] ? $this->channels->resolve($first) : null,
            lastTouch: $last,
            firstTouch: $first,
            pages: array_values((array) ($state['pages'] ?? [])),
            fbc: $this->fbc($request, $last),
            fbp: $this->cookie($request, '_fbp'),
            gaClientId: $this->gaClientId($request),
            deviceType: self::deviceType((string) $request->userAgent()),
            ipHash: self::hashIp($request->ip()),
            userAgent: Str::limit((string) $request->userAgent(), 490, ''),
        );
    }

    /**
     * The visitor's session. Livewire requests carry it; if a request has none
     * (e.g. called outside the web middleware), use the application's session store.
     */
    private function session(Request $request): ?Session
    {
        if ($request->hasSession()) {
            return $request->session();
        }

        return app()->bound('session.store') ? app('session.store') : null;
    }

    /**
     * @return array<string, string|null>
     */
    private function touchFrom(Request $request): array
    {
        $touch = [];

        foreach (self::PARAMETERS as $parameter) {
            $value = $request->query($parameter);
            $touch[$parameter] = is_string($value) && $value !== '' ? Str::limit($this->clean($value), 250, '') : null;
        }

        $touch['landing_page'] = Str::limit($request->getRequestUri(), 490, '');
        $touch['referrer'] = $this->externalReferrer($request);
        $touch['captured_at'] = now()->toIso8601String();

        return $touch;
    }

    /**
     * @param  array<string, string|null>  $touch
     */
    private function isCampaignTouch(array $touch): bool
    {
        foreach ([...self::PARAMETERS, 'referrer'] as $key) {
            if (filled($touch[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The referring page without its query string (which may hold personal data),
     * or null when the visitor came from this site or typed the address.
     */
    private function externalReferrer(Request $request): ?string
    {
        $referrer = (string) $request->headers->get('referer', '');
        $parts = parse_url($referrer);

        if ($referrer === '' || ! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        if (mb_strtolower($parts['host']) === mb_strtolower($request->getHost())) {
            return null;
        }

        return Str::limit(($parts['scheme'] ?? 'https').'://'.$parts['host'].($parts['path'] ?? ''), 490, '');
    }

    /**
     * Meta's click cookie: the browser's _fbc if the pixel set one, otherwise
     * built from the captured fbclid ("fb.1.{milliseconds}.{fbclid}").
     *
     * @param  array<string, string|null>  $touch
     */
    private function fbc(Request $request, array $touch): ?string
    {
        if (($cookie = $this->cookie($request, '_fbc')) !== null) {
            return $cookie;
        }

        if (blank($touch['fbclid'] ?? null)) {
            return null;
        }

        $capturedAt = isset($touch['captured_at']) ? strtotime((string) $touch['captured_at']) : false;

        return 'fb.1.'.(($capturedAt ?: time()) * 1000).'.'.$touch['fbclid'];
    }

    /**
     * GA4's client ID from the _ga cookie ("GA1.1.123456789.1700000000" → "123456789.1700000000").
     */
    private function gaClientId(Request $request): ?string
    {
        $cookie = $this->cookie($request, '_ga');

        if ($cookie === null || preg_match('/^GA\d\.\d\.(\d+\.\d+)$/', $cookie, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function cookie(Request $request, string $name): ?string
    {
        $value = $request->cookies->get($name);

        return is_string($value) && $value !== '' ? Str::limit($this->clean($value), 250, '') : null;
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
    }

    public static function deviceType(string $userAgent): string
    {
        return match (true) {
            preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $userAgent) === 1 => 'tablet',
            preg_match('/Mobi|iPhone|iPod|Android|Windows Phone/i', $userAgent) === 1 => 'mobile',
            default => 'desktop',
        };
    }

    /**
     * A keyed hash of the IP: enough to spot abuse, without storing the address.
     */
    public static function hashIp(?string $ip): ?string
    {
        return $ip === null ? null : hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
