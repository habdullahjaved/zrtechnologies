<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The marketing channel a lead is credited to (brief §8.3).
 */
enum Channel: string implements HasColor, HasLabel
{
    case GoogleAds = 'google_ads';
    case GoogleBusinessProfile = 'google_business_profile';
    case GoogleOrganic = 'google_organic';
    case MetaAds = 'meta_ads';
    case MetaOrganic = 'meta_organic';
    case MicrosoftAds = 'microsoft_ads';
    case TikTokAds = 'tiktok_ads';
    case OtherSearch = 'other_search';
    case Email = 'email';
    case Referral = 'referral';
    case OtherCampaign = 'other_campaign';
    case Direct = 'direct';
    case Chatbot = 'chatbot';
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';

    public function getLabel(): string
    {
        return match ($this) {
            self::GoogleAds => 'Google Ads',
            self::GoogleBusinessProfile => 'Google Business Profile',
            self::GoogleOrganic => 'Google organic',
            self::MetaAds => 'Meta ads',
            self::MetaOrganic => 'Facebook / Instagram',
            self::MicrosoftAds => 'Microsoft Ads',
            self::TikTokAds => 'TikTok ads',
            self::OtherSearch => 'Other search engines',
            self::Email => 'Email',
            self::Referral => 'Referral',
            self::OtherCampaign => 'Other campaign',
            self::Direct => 'Direct',
            self::Chatbot => 'Chatbot',
            self::WhatsApp => 'WhatsApp',
            self::Phone => 'Phone',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::GoogleAds, self::MetaAds, self::MicrosoftAds, self::TikTokAds => 'warning',
            self::GoogleBusinessProfile, self::GoogleOrganic, self::OtherSearch => 'success',
            self::MetaOrganic, self::Referral, self::Email, self::OtherCampaign => 'info',
            default => 'gray',
        };
    }

    public function isPaid(): bool
    {
        return in_array($this, [self::GoogleAds, self::MetaAds, self::MicrosoftAds, self::TikTokAds], true);
    }

    /**
     * Channels staff can pick for an enquiry that came in by phone or message.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        $options = [];

        foreach ([self::Phone, self::WhatsApp, self::Email, self::Referral, self::GoogleBusinessProfile, self::MetaOrganic, self::Direct] as $channel) {
            $options[$channel->value] = $channel->getLabel();
        }

        return $options;
    }
}
