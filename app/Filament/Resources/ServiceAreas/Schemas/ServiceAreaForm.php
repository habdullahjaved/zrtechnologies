<?php

namespace App\Filament\Resources\ServiceAreas\Schemas;

use App\Filament\Resources\CatalogueFields;
use App\Filament\Resources\ContentFields;
use App\Filament\Resources\RepairPrices\RepairPriceResource;
use App\Models\ServiceArea;
use App\Services\Seo\SeoResolver;
use App\Settings\SiteSettings;
use App\Support\Postcode;
use Closure;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ServiceAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Area')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    CatalogueFields::name()
                        ->placeholder('e.g. Accrington & Oswaldtwistle'),
                    CatalogueFields::slug(ServiceArea::class, 'Used for the area page, e.g. /iphone-repair-darwen.'),
                    TextInput::make('town')
                        ->helperText('The main town, used in page titles ("iPhone Repair in …").')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('county')
                        ->required()
                        ->maxLength(100)
                        ->default('Lancashire'),
                    Textarea::make('summary')
                        ->helperText('Neighbourhoods and villages covered, in one or two sentences.')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),
            Section::make('Coverage')
                ->description('A postcode is covered if its district is listed below, or if it lies within the radius of the centre point.')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TagsInput::make('postcode_districts')
                        ->label('Postcode districts')
                        ->placeholder('Type a district, e.g. BB2, and press Enter')
                        ->helperText('The first half of a postcode.')
                        ->splitKeys(['Tab', ',', ' '])
                        ->required()
                        ->dehydrateStateUsing(fn (?array $state): array => array_values(array_unique(array_map(
                            fn (string $district): string => strtoupper(trim($district)),
                            $state ?? [],
                        ))))
                        ->nestedRecursiveRules([
                            fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (Postcode::outcode((string) $value) !== strtoupper(trim((string) $value))) {
                                    $fail("\"{$value}\" is not a postcode district. Use the first half of a postcode, e.g. BB2.");
                                }
                            },
                        ])
                        ->columnSpanFull(),
                    TextInput::make('centre_lat')
                        ->label('Centre latitude')
                        ->numeric()
                        ->minValue(49)
                        ->maxValue(61)
                        ->requiredWith('radius_km'),
                    TextInput::make('centre_lng')
                        ->label('Centre longitude')
                        ->numeric()
                        ->minValue(-9)
                        ->maxValue(2)
                        ->requiredWith('radius_km'),
                    TextInput::make('radius_km')
                        ->label('Radius')
                        ->suffix('km')
                        ->helperText('Optional. Also covers postcodes this close to the centre, whatever their district.')
                        ->numeric()
                        ->minValue(0.5)
                        ->maxValue(80),
                    Text::make(fn (Get $get): HtmlString|string => filled($get('centre_lat')) && filled($get('centre_lng'))
                        ? new HtmlString('<a class="text-primary-600 underline" target="_blank" rel="noopener" href="https://www.openstreetmap.org/?mlat='.e((string) $get('centre_lat')).'&mlon='.e((string) $get('centre_lng')).'#map=12/'.e((string) $get('centre_lat')).'/'.e((string) $get('centre_lng')).'">Check the centre point on a map ↗</a>')
                        : 'Add a centre point to check it on a map.')
                        ->columnSpanFull(),
                ]),
            Section::make('Collection')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    RepairPriceResource::poundsInput('collection_fee_pence')
                        ->label('Collection & return fee')
                        ->helperText('Charged once per booking. Enter 0 for free collection.')
                        ->required()
                        ->default('0.00'),
                    TextInput::make('typical_collection_times')
                        ->label('Typical collection times')
                        ->maxLength(200),
                    Toggle::make('is_active')
                        ->label('We collect here')
                        ->helperText('Switch off to stop accepting bookings from this area.')
                        ->default(true),
                    Toggle::make('is_draft')
                        ->label('Draft – confirm before launch')
                        ->helperText('Switch off once the districts and fee are confirmed.')
                        ->default(false),
                ]),
            Section::make('Location page')
                ->description('The town’s own page, /iphone-repair-{slug}. Write genuinely local content: it stays hidden from search engines until it is long enough and not a copy of another town’s page.')
                ->columnSpanFull()
                ->schema([
                    Text::make(fn (?ServiceArea $record): HtmlString => self::contentCheck($record))
                        ->columnSpanFull(),
                    Textarea::make('intro')
                        ->label('Introduction')
                        ->helperText('Shown under the page heading.')
                        ->rows(3)
                        ->maxLength(1000),
                    Textarea::make('landmarks')
                        ->label('Local areas and landmarks')
                        ->helperText('Neighbourhoods, estates, landmarks and where you usually meet customers. Leave a blank line between paragraphs.')
                        ->rows(5)
                        ->maxLength(5000),
                    ContentFields::blocks(),
                    ContentFields::faqs(),
                ]),
            ContentFields::seo(
                defaultTitle: fn (Get $get): string => app(SeoResolver::class)->pattern(app(SiteSettings::class)->title_pattern_location, ['town' => $get('../town') ?: 'Town']),
                defaultDescription: fn (Get $get): ?string => filled($get('../town')) ? "iPhone repair in {$get('../town')} with collection and delivery." : null,
                url: fn (?Model $record): ?string => $record instanceof ServiceArea ? $record->url() : null,
                contentText: fn (Get $get): string => implode(' ', [$get('../summary'), $get('../intro'), $get('../landmarks'), ContentFields::blocksText($get('../blocks'))]),
            ),
        ]);
    }

    /**
     * Word count and similarity for the location page, saved after each change.
     */
    private static function contentCheck(?ServiceArea $area): HtmlString
    {
        if ($area === null) {
            return new HtmlString('Save the area first; the content check runs every time it is saved.');
        }

        $site = app(SiteSettings::class);
        $passes = $area->passesContentChecks();

        $status = $passes
            ? '<strong style="color:#15803D">✓ Search engines may index this page.</strong>'
            : '<strong style="color:#B45309">! Hidden from search engines until the checks below pass.</strong>';

        return new HtmlString($status.'<br>'
            .e("Unique local content: {$area->unique_word_count} words (needs {$site->min_unique_words_location}). ")
            .e('Closest match with another town: '.number_format((float) $area->similarity_score).'% (must be '.$site->max_similarity_percent.'% or less).'));
    }
}
