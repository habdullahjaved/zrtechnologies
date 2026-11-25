<?php

namespace App\Filament\Pages\Settings;

use App\Http\Controllers\OfflineConversionExportController;
use App\Settings\TrackingSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * Analytics and advertising IDs. All optional: a platform starts working as
 * soon as its ID is saved, and only for visitors who consent.
 */
class ManageTrackingSettings extends BaseSettingsPage
{
    use HasPageShield;

    protected static string $settings = TrackingSettings::class;

    protected static ?string $slug = 'settings/tracking';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Tracking';

    protected static ?string $title = 'Tracking and advertising';

    protected static ?int $navigationSort = 60;

    public function getSubheading(): ?string
    {
        return 'Tags only load after a visitor accepts cookies. Leave a field empty to switch that platform off. Check everything works on Marketing → Tracking health.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Google Tag Manager')
                ->description('Optional. With a container ID, the website sends its events to Tag Manager and your tags there decide what fires (import docs/gtm-container.json to start). Leave it empty to let the website load Google Analytics, Google Ads, Meta, TikTok, Pinterest and Clarity itself.')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('gtm_container_id')
                        ->label('Container ID')
                        ->placeholder('GTM-XXXXXXX')
                        ->regex('/^GTM-[A-Z0-9]+$/')
                        ->maxLength(20),
                ]),
            Section::make('Google Analytics 4')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('ga4_measurement_id')
                        ->label('Measurement ID')
                        ->placeholder('G-XXXXXXXXXX')
                        ->regex('/^G-[A-Z0-9]+$/')
                        ->maxLength(20),
                    TextInput::make('ga4_api_secret')
                        ->label('Measurement Protocol API secret')
                        ->helperText('GA4 → Admin → Data streams → your stream → Measurement Protocol API secrets. Used to record completed repairs as purchases.')
                        ->password()
                        ->revealable()
                        ->maxLength(100),
                ]),
            Section::make('Google Ads')
                ->description('Conversion labels come from Google Ads → Goals → Conversions → each action → Tag setup.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('google_ads_conversion_id')
                        ->label('Conversion ID')
                        ->placeholder('AW-123456789')
                        ->regex('/^AW-\d+$/')
                        ->maxLength(20),
                    Toggle::make('enhanced_conversions')
                        ->label('Enhanced conversions')
                        ->helperText('Sends the customer’s email and phone, hashed, with each conversion so Google can match more of them.'),
                    TextInput::make('google_ads_booking_label')->label('Label: online booking')->maxLength(60),
                    TextInput::make('google_ads_lead_label')->label('Label: quote, callback and contact forms')->maxLength(60),
                    TextInput::make('google_ads_call_label')->label('Label: phone number tapped')->maxLength(60),
                    TextInput::make('google_ads_whatsapp_label')->label('Label: WhatsApp tapped')->maxLength(60),
                    TextInput::make('google_ads_offline_conversion_name')
                        ->label('Offline conversion action name')
                        ->helperText('The exact name of the "import from clicks" conversion action for completed repairs.')
                        ->required()
                        ->maxLength(100),
                    Text::make(fn (TrackingSettings $settings): HtmlString => new HtmlString(
                        '<strong>Offline conversions link</strong> (add as a scheduled upload in Google Ads; keep it private):<br><code style="word-break:break-all">'.e(OfflineConversionExportController::url($settings)).'</code>'
                    ))->columnSpanFull(),
                ]),
            Section::make('Meta (Facebook and Instagram)')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('meta_pixel_id')
                        ->label('Pixel (dataset) ID')
                        ->regex('/^\d+$/')
                        ->maxLength(30),
                    TextInput::make('meta_capi_token')
                        ->label('Conversions API access token')
                        ->helperText('Events Manager → your dataset → Settings → Conversions API → Generate access token.')
                        ->password()
                        ->revealable()
                        ->maxLength(500),
                    TextInput::make('meta_test_event_code')
                        ->label('Test event code')
                        ->helperText('Events Manager → Test events. Only used while test mode is on.')
                        ->maxLength(30),
                ]),
            Section::make('Other tools')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('clarity_project_id')
                        ->label('Microsoft Clarity project ID')
                        ->helperText('Loads only for visitors who accept analytics cookies.')
                        ->maxLength(30),
                    TextInput::make('tiktok_pixel_id')
                        ->label('TikTok Pixel ID')
                        ->helperText('Loads only for visitors who accept marketing cookies.')
                        ->maxLength(40),
                    TextInput::make('pinterest_tag_id')
                        ->label('Pinterest Tag ID')
                        ->helperText('Pinterest Ads → Conversions → Pinterest Tag (the number). Loads only for visitors who accept marketing cookies. The website verification code goes in Settings → Website & SEO.')
                        ->regex('/^\d{6,20}$/')
                        ->validationMessages(['regex' => 'The Pinterest Tag ID is a number, e.g. 2612345678901.'])
                        ->maxLength(20),
                ]),
            Section::make('Testing')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('test_mode')
                        ->label('Test mode')
                        ->helperText('Server-side events go to Meta’s Test events tab and GA4’s validation endpoint instead of your live reports. Switch off before launch.'),
                ]),
        ]);
    }
}
