<?php

namespace App\Filament\Pages\Settings;

use App\Settings\SiteSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSiteSettings extends BaseSettingsPage
{
    use HasPageShield;

    protected static string $settings = SiteSettings::class;

    protected static ?string $slug = 'settings/website';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Website & SEO';

    protected static ?string $title = 'Website & SEO defaults';

    protected static ?int $navigationSort = 40;

    public function getSubheading(): ?string
    {
        return 'Defaults used when a page has no title, description or share image of its own.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Search engines')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('allow_indexing')
                        ->label('Allow search engines to index the website')
                        ->helperText(app()->isProduction()
                            ? 'Switch on at launch. When off, every page tells search engines not to index it.'
                            : 'This only takes effect on the live (production) website. Other environments are never indexed.'),
                ]),
            Section::make('Default titles and descriptions')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('site_name')
                        ->label('Site name')
                        ->helperText('Appended to page titles, e.g. "Battery Replacement | ZR Technologies".')
                        ->required()
                        ->maxLength(60),
                    TextInput::make('title_separator')
                        ->label('Title separator')
                        ->required()
                        ->maxLength(3),
                    TextInput::make('default_meta_title')
                        ->label('Default page title')
                        ->helperText('Aim for 50–60 characters.')
                        ->required()
                        ->maxLength(70)
                        ->columnSpanFull(),
                    Textarea::make('default_meta_description')
                        ->label('Default meta description')
                        ->helperText('Aim for 140–160 characters.')
                        ->required()
                        ->rows(3)
                        ->maxLength(170)
                        ->columnSpanFull(),
                    TextInput::make('default_og_image')
                        ->label('Default share image')
                        ->helperText('Path inside /public to a 1200×630 image, used when a page has no share image of its own.')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
            Section::make('Notices')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('announcement')
                        ->label('Announcement bar')
                        ->helperText('Optional one-line message shown above the header on every page (e.g. holiday hours). Leave empty to hide.')
                        ->maxLength(160),
                    Textarea::make('trademark_disclaimer')
                        ->label('Trademark disclaimer')
                        ->helperText('Shown in the footer of every page.')
                        ->required()
                        ->rows(2)
                        ->maxLength(400),
                ]),
        ]);
    }
}
