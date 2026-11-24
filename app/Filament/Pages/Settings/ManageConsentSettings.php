<?php

namespace App\Filament\Pages\Settings;

use App\Settings\ConsentSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageConsentSettings extends BaseSettingsPage
{
    use HasPageShield;

    protected static string $settings = ConsentSettings::class;

    protected static ?string $slug = 'settings/cookies';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static ?string $navigationLabel = 'Cookie banner';

    protected static ?string $title = 'Cookie banner';

    protected static ?int $navigationSort = 61;

    public function getSubheading(): ?string
    {
        return 'The wording visitors see before any analytics or marketing cookies are set. Every choice is logged under Marketing → Consent log.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Banner')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('banner_title')->required()->maxLength(80),
                    Textarea::make('banner_text')->required()->rows(4)->maxLength(600),
                    Textarea::make('analytics_description')->label('Analytics cookies: description')->required()->rows(2)->maxLength(300),
                    Textarea::make('marketing_description')->label('Marketing cookies: description')->required()->rows(2)->maxLength(300),
                ]),
            Section::make('Policy version')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('policy_version')
                        ->helperText('Change this (e.g. to today’s date) after adding new cookies or changing the cookie policy: every visitor will be asked again.')
                        ->required()
                        ->regex('/^[A-Za-z0-9._-]+$/')
                        ->maxLength(20),
                ]),
        ]);
    }
}
