<?php

namespace App\Filament\Pages\Settings;

use App\Settings\SocialSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSocialSettings extends BaseSettingsPage
{
    use HasPageShield;

    protected static string $settings = SocialSettings::class;

    protected static ?string $slug = 'settings/social';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static ?string $navigationLabel = 'Social & listings';

    protected static ?string $title = 'Social profiles & listings';

    protected static ?int $navigationSort = 30;

    public function getSubheading(): ?string
    {
        return 'Add the full web address of each profile. Filled profiles appear in the footer and tell search engines these accounts belong to you. Empty ones stay hidden.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Google')
                ->columnSpanFull()
                ->schema([
                    $this->urlField('google_business_profile', 'Google Business Profile', 'https://maps.app.goo.gl/…'),
                    $this->urlField('google_review_url', 'Google review link', 'https://g.page/r/…/review')
                        ->helperText('In your Google Business Profile, choose "Ask for reviews" and paste the link here. Used by the "Leave a review" buttons and the short link /reviews/leave.'),
                ]),
            Section::make('Social networks')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    $this->urlField('facebook', 'Facebook', 'https://www.facebook.com/…'),
                    $this->urlField('instagram', 'Instagram', 'https://www.instagram.com/…'),
                    $this->urlField('tiktok', 'TikTok', 'https://www.tiktok.com/@…'),
                    $this->urlField('youtube', 'YouTube', 'https://www.youtube.com/@…'),
                    $this->urlField('linkedin', 'LinkedIn', 'https://www.linkedin.com/company/…'),
                    $this->urlField('x', 'X (Twitter)', 'https://x.com/…'),
                ]),
            Section::make('Marketplaces')
                ->columnSpanFull()
                ->schema([
                    $this->urlField('ebay_store', 'eBay store', 'https://www.ebay.co.uk/str/…'),
                ]),
        ]);
    }

    private function urlField(string $name, string $label, string $placeholder): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->url()
            ->placeholder($placeholder)
            ->maxLength(255);
    }
}
