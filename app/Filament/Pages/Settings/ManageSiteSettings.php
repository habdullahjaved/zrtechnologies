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
            Section::make('Site verification')
                ->description('Paste the code each service gives you for the "HTML tag" / "meta tag" method. You can paste the whole <meta ...> tag: only the code is kept. Each tag is added to every page.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    self::verificationInput('google_site_verification', 'Google Search Console', 'Search Console → Add property → URL prefix → HTML tag.'),
                    self::verificationInput('bing_site_verification', 'Bing Webmaster Tools', 'Bing Webmaster Tools → Add site → HTML Meta Tag (msvalidate.01). Or import from Search Console instead.'),
                    self::verificationInput('pinterest_site_verification', 'Pinterest', 'Pinterest business account → Settings → Claimed accounts → Website → Add HTML tag.'),
                    self::verificationInput('facebook_domain_verification', 'Meta (Facebook) domain', 'Meta Business Suite → Settings → Brand safety → Domains → Meta-tag verification.'),
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
            Section::make('Title patterns')
                ->description('Automatic page titles, used when a page has no SEO title of its own. Placeholders: {name}, {model}, {repair}, {town}, {title} and {price} (the "from" price). Anything after a | that has an empty placeholder is left out. The site name is added at the end.')
                ->columns(2)
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    TextInput::make('title_pattern_service')
                        ->label('Repair pages')
                        ->helperText('e.g. "iPhone Screen Replacement in Blackburn | From £49"')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('title_pattern_model')
                        ->label('Model pages')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('title_pattern_model_repair')
                        ->label('Model × repair pages')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('title_pattern_location')
                        ->label('Location pages')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('title_pattern_post')
                        ->label('Blog posts')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('title_pattern_post_category')
                        ->label('Blog categories')
                        ->required()
                        ->maxLength(120),
                ]),
            Section::make('Thin-content protection')
                ->description('Location and model × repair pages are hidden from search engines (and left out of the sitemap) until their own content passes these checks, so they can never look like doorway pages.')
                ->columns(3)
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    TextInput::make('min_unique_words_location')
                        ->label('Words needed: location pages')
                        ->integer()
                        ->minValue(100)
                        ->maxValue(3000)
                        ->required(),
                    TextInput::make('min_unique_words_model_repair')
                        ->label('Words needed: model × repair pages')
                        ->integer()
                        ->minValue(100)
                        ->maxValue(3000)
                        ->required(),
                    TextInput::make('max_similarity_percent')
                        ->label('Maximum similarity to another page')
                        ->suffix('%')
                        ->integer()
                        ->minValue(10)
                        ->maxValue(90)
                        ->required(),
                ]),
            Section::make('Crawling')
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    Toggle::make('index_tag_pages')
                        ->label('Let search engines index blog tag pages')
                        ->helperText('Off by default: tag pages repeat the posts already on category pages.'),
                    Textarea::make('robots_extra')
                        ->label('Extra robots.txt lines')
                        ->helperText('Added to robots.txt when indexing is on, one rule per line, e.g. "Disallow: /*?sort=". The admin, private customer pages and the sitemap line are added automatically.')
                        ->rows(4)
                        ->maxLength(2000),
                ]),
            Section::make('Home page')
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    Toggle::make('show_coming_soon')
                        ->label('Show the "coming soon" teaser')
                        ->helperText('Lists services that are switched off in Catalogue → Service categories (accessories, custom printing...).'),
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

    private static function verificationInput(string $name, string $label, string $helperText): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->helperText($helperText)
            ->maxLength(300)
            ->dehydrateStateUsing(fn (?string $state): ?string => self::verificationCode($state));
    }

    /**
     * Keeps only the code when a whole meta tag or a "name=code" TXT value is pasted.
     */
    public static function verificationCode(?string $value): ?string
    {
        $value = trim((string) $value);

        if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $value, $matches) === 1) {
            $value = trim($matches[1]);
        } elseif (preg_match('/^[a-z0-9_.:-]+-verification=(.+)$/i', $value, $matches) === 1) {
            $value = trim($matches[1]);
        }

        return $value === '' ? null : $value;
    }
}
