<?php

namespace App\Services\Attribution;

use App\Enums\Channel;

/**
 * Decides which marketing channel a visit came from, using click IDs, UTM
 * tags and the referrer, in that order of trust.
 *
 * Google Business Profile links should carry
 * ?utm_source=google&utm_medium=organic&utm_campaign=gbp (brief §8.3).
 */
final class ChannelResolver
{
    private const array PAID_MEDIUMS = ['cpc', 'ppc', 'paid', 'paidsearch', 'paid_search', 'paid-search', 'paidsocial', 'paid_social', 'paid-social', 'cpm', 'display', 'ads', 'ad'];

    private const array META_SOURCES = ['facebook', 'fb', 'instagram', 'ig', 'meta', 'messenger'];

    private const array GBP_MARKERS = ['gbp', 'gmb', 'google_business_profile', 'google-business-profile', 'googlebusinessprofile'];

    private const array SEARCH_ENGINES = ['bing.', 'duckduckgo.', 'yahoo.', 'ecosia.', 'yandex.', 'baidu.', 'search.brave.com', 'startpage.'];

    private const array MAIL_HOSTS = ['mail.google.com', 'outlook.live.com', 'outlook.office.com', 'mail.yahoo.com', 'mail.aol.com'];

    /**
     * @param  array<string, mixed>  $touch  One touch from the attribution snapshot.
     */
    public function resolve(array $touch): Channel
    {
        $value = fn (string $key): string => mb_strtolower(trim((string) ($touch[$key] ?? '')));

        if ($value('gclid') !== '' || $value('gbraid') !== '' || $value('wbraid') !== '') {
            return Channel::GoogleAds;
        }

        if ($value('msclkid') !== '') {
            return Channel::MicrosoftAds;
        }

        if ($value('ttclid') !== '') {
            return Channel::TikTokAds;
        }

        $source = $value('utm_source');
        $medium = $value('utm_medium');
        $campaign = $value('utm_campaign');

        if ($source !== '' || $medium !== '' || $campaign !== '') {
            return $this->fromUtm($source, $medium, $campaign, $value('fbclid') !== '');
        }

        if ($value('fbclid') !== '') {
            return Channel::MetaAds;
        }

        $referrer = $value('referrer');

        if ($referrer !== '') {
            return $this->fromReferrer($referrer);
        }

        return Channel::Direct;
    }

    private function fromUtm(string $source, string $medium, string $campaign, bool $hasFbclid): Channel
    {
        if (array_intersect([$source, $medium, $campaign], self::GBP_MARKERS) !== []) {
            return Channel::GoogleBusinessProfile;
        }

        $isMeta = in_array($source, self::META_SOURCES, true);

        if (in_array($medium, self::PAID_MEDIUMS, true)) {
            return match (true) {
                $source === 'google' => Channel::GoogleAds,
                $isMeta => Channel::MetaAds,
                in_array($source, ['bing', 'microsoft'], true) => Channel::MicrosoftAds,
                $source === 'tiktok' => Channel::TikTokAds,
                default => Channel::OtherCampaign,
            };
        }

        if (in_array($medium, ['email', 'e-mail', 'newsletter'], true)) {
            return Channel::Email;
        }

        if ($isMeta) {
            return in_array($medium, ['social', 'organic', 'post', 'bio', 'profile', 'story'], true) || ! $hasFbclid
                ? Channel::MetaOrganic
                : Channel::MetaAds;
        }

        if ($source === 'google' && $medium === 'organic') {
            return Channel::GoogleOrganic;
        }

        return Channel::OtherCampaign;
    }

    private function fromReferrer(string $referrer): Channel
    {
        $host = (string) parse_url(str_contains($referrer, '://') ? $referrer : 'https://'.$referrer, PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        if (in_array($host, self::MAIL_HOSTS, true)) {
            return Channel::Email;
        }

        if (preg_match('/(^|\.)google\.[a-z.]+$/', $host) === 1) {
            return Channel::GoogleOrganic;
        }

        foreach (self::SEARCH_ENGINES as $engine) {
            if (str_contains($host, $engine)) {
                return Channel::OtherSearch;
            }
        }

        if (preg_match('/(^|\.)(facebook\.com|fb\.com|fb\.me|instagram\.com|messenger\.com)$/', $host) === 1) {
            return Channel::MetaOrganic;
        }

        return Channel::Referral;
    }
}
