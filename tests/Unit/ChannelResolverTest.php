<?php

use App\Enums\Channel;
use App\Services\Attribution\ChannelResolver;

it('credits each visit to the right channel', function (array $touch, Channel $expected) {
    expect((new ChannelResolver)->resolve($touch))->toBe($expected);
})->with([
    'Google Ads click ID' => [['gclid' => 'TEST123', 'utm_source' => 'google', 'utm_medium' => 'cpc'], Channel::GoogleAds],
    'Google Ads iOS click ID' => [['gbraid' => 'abc'], Channel::GoogleAds],
    'Google Ads tagged by UTM only' => [['utm_source' => 'google', 'utm_medium' => 'cpc'], Channel::GoogleAds],
    'Google Business Profile link' => [['utm_source' => 'google', 'utm_medium' => 'organic', 'utm_campaign' => 'gbp'], Channel::GoogleBusinessProfile],
    'Google organic search' => [['referrer' => 'https://www.google.co.uk/'], Channel::GoogleOrganic],
    'Meta click ID' => [['fbclid' => 'IwAR123'], Channel::MetaAds],
    'Meta paid social UTM' => [['utm_source' => 'facebook', 'utm_medium' => 'paid_social', 'fbclid' => 'IwAR123'], Channel::MetaAds],
    'Facebook post' => [['utm_source' => 'facebook', 'utm_medium' => 'social'], Channel::MetaOrganic],
    'Instagram referrer' => [['referrer' => 'https://l.instagram.com/'], Channel::MetaOrganic],
    'Microsoft Ads' => [['msclkid' => 'abc'], Channel::MicrosoftAds],
    'TikTok Ads' => [['ttclid' => 'abc'], Channel::TikTokAds],
    'Bing search' => [['referrer' => 'https://www.bing.com/search'], Channel::OtherSearch],
    'Gmail link' => [['referrer' => 'https://mail.google.com/'], Channel::Email],
    'Newsletter' => [['utm_source' => 'newsletter', 'utm_medium' => 'email'], Channel::Email],
    'Another website' => [['referrer' => 'https://www.lancashiretelegraph.co.uk/news'], Channel::Referral],
    'Unknown campaign' => [['utm_source' => 'flyer', 'utm_campaign' => 'autumn'], Channel::OtherCampaign],
    'Typed in or bookmarked' => [['landing_page' => '/'], Channel::Direct],
]);
