<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Social profiles and business listings. Filled links appear in the footer and
 * in the structured-data "sameAs" list; empty ones are hidden everywhere.
 */
class SocialSettings extends Settings
{
    public ?string $google_business_profile;

    public ?string $facebook;

    public ?string $instagram;

    public ?string $tiktok;

    public ?string $youtube;

    public ?string $linkedin;

    public ?string $x;

    public ?string $ebay_store;

    /** Google "write a review" link (https://g.page/r/.../review), used for review requests. */
    public ?string $google_review_url;

    /** Footer-visible networks, in display order, with their labels. */
    public const array NETWORKS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'x' => 'X (Twitter)',
    ];

    public static function group(): string
    {
        return 'social';
    }

    /**
     * Footer networks that have a URL set.
     *
     * @return array<string, array{label: string, url: string}>
     */
    public function filledNetworks(): array
    {
        $networks = [];

        foreach (self::NETWORKS as $key => $label) {
            if (filled($this->{$key})) {
                $networks[$key] = ['label' => $label, 'url' => $this->{$key}];
            }
        }

        return $networks;
    }

    /**
     * Every filled profile URL, for structured data "sameAs".
     *
     * @return list<string>
     */
    public function sameAs(): array
    {
        return array_values(array_filter([
            $this->google_business_profile,
            $this->facebook,
            $this->instagram,
            $this->tiktok,
            $this->youtube,
            $this->linkedin,
            $this->x,
            $this->ebay_store,
        ]));
    }
}
