<?php

namespace App\Data;

use App\Enums\Channel;

/**
 * Where a visitor came from, captured when they become a lead.
 */
final readonly class AttributionData
{
    /**
     * @param  array<string, string|null>  $lastTouch
     * @param  array<string, string|null>  $firstTouch
     * @param  list<array{path: string, at: string}>  $pages
     */
    public function __construct(
        public Channel $channel,
        public ?Channel $firstTouchChannel = null,
        public array $lastTouch = [],
        public array $firstTouch = [],
        public array $pages = [],
        public ?string $fbc = null,
        public ?string $fbp = null,
        public ?string $gaClientId = null,
        public ?string $deviceType = null,
        public ?string $ipHash = null,
        public ?string $userAgent = null,
    ) {}

    /**
     * A lead added by staff (phone call, WhatsApp message) with no web visit behind it.
     */
    public static function manual(Channel $channel): self
    {
        return new self(channel: $channel, firstTouchChannel: $channel);
    }

    /**
     * Columns for the leads table.
     *
     * @return array<string, mixed>
     */
    public function toLeadAttributes(): array
    {
        $touch = fn (string $key): ?string => $this->lastTouch[$key] ?? null;

        return [
            'channel' => $this->channel,
            'first_touch_channel' => $this->firstTouchChannel,
            'gclid' => $touch('gclid'),
            'gbraid' => $touch('gbraid'),
            'wbraid' => $touch('wbraid'),
            'fbclid' => $touch('fbclid'),
            'fbc' => $this->fbc,
            'fbp' => $this->fbp,
            'msclkid' => $touch('msclkid'),
            'ttclid' => $touch('ttclid'),
            'utm_source' => $touch('utm_source'),
            'utm_medium' => $touch('utm_medium'),
            'utm_campaign' => $touch('utm_campaign'),
            'utm_term' => $touch('utm_term'),
            'utm_content' => $touch('utm_content'),
            'landing_page' => $touch('landing_page'),
            'referrer' => $touch('referrer'),
            'device_type' => $this->deviceType,
            'ga_client_id' => $this->gaClientId,
            'attribution' => [
                'first_touch' => $this->firstTouch ?: null,
                'last_touch' => $this->lastTouch ?: null,
                'pages' => $this->pages,
            ],
            'ip_hash' => $this->ipHash,
            'user_agent' => $this->userAgent,
        ];
    }
}
