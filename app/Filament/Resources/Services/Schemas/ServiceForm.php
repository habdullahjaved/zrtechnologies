<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Resources\CatalogueFields;
use App\Filament\Resources\ContentFields;
use App\Filament\Resources\RepairTypes\Schemas\RepairTypeForm;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Seo\SeoResolver;
use App\Settings\SiteSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Repair page')
                ->columns(2)
                ->columnSpan(2)
                ->schema([
                    CatalogueFields::name()
                        ->label('Repair name')
                        ->placeholder('e.g. Screen Replacement'),
                    CatalogueFields::slug(Service::class, 'The page address: /iphone-repair/{slug}. Changing it later redirects the old address automatically.'),
                    TextInput::make('heading')
                        ->label('Heading (H1)')
                        ->placeholder(fn (Get $get): string => 'iPhone '.($get('name') ?: 'Screen Replacement').' in Blackburn')
                        ->helperText('Leave empty to use "iPhone {name} in Blackburn".')
                        ->maxLength(160)
                        ->columnSpanFull(),
                    Textarea::make('summary')
                        ->helperText('One or two sentences for repair cards, the menu and the meta description. Up to 300 characters.')
                        ->rows(2)
                        ->maxLength(300)
                        ->columnSpanFull(),
                    Textarea::make('intro')
                        ->label('Introduction')
                        ->helperText('Shown under the heading at the top of the page.')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    Select::make('repairTypes')
                        ->label('Repairs covered')
                        ->helperText('The prices of these repairs are listed on the page. The page is hidden while none of them are offered.')
                        ->relationship('repairTypes', 'name')
                        ->multiple()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                ]),
            Section::make('Display')
                ->columnSpan(1)
                ->schema([
                    Select::make('service_category_id')
                        ->label('Service')
                        ->relationship('serviceCategory', 'name')
                        ->default(fn (): ?int => ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->value('id'))
                        ->required(),
                    Select::make('icon')
                        ->options(RepairTypeForm::ICONS)
                        ->native(false),
                    Select::make('og_image')
                        ->label('Share image')
                        ->options(fn (): array => ContentFields::suppliedShareImages())
                        ->helperText('Used when the page is shared. Upload a custom image in the SEO section instead if you prefer.'),
                    Toggle::make('is_active')
                        ->label('Show on the website')
                        ->default(true),
                    TextInput::make('sort')
                        ->label('Sort order')
                        ->integer()
                        ->minValue(0)
                        ->default(fn (): int => (int) Service::query()->max('sort') + 10)
                        ->required(),
                ]),
            Section::make('Content')
                ->description('The price table, booking buttons, FAQs and related guides are added automatically.')
                ->columnSpanFull()
                ->schema([
                    ContentFields::blocks(),
                    ContentFields::faqs(),
                ]),
            ContentFields::seo(
                defaultTitle: fn (Get $get): string => app(SeoResolver::class)->pattern(app(SiteSettings::class)->title_pattern_service, [
                    'name' => 'iPhone '.($get('../name') ?: 'repair'),
                    'price' => '£…',
                ]),
                defaultDescription: fn (Get $get): ?string => $get('../summary'),
                url: fn (?Model $record): ?string => $record instanceof Service ? $record->url() : null,
                contentText: fn (Get $get): string => $get('../intro').' '.ContentFields::blocksText($get('../blocks')),
            ),
        ]);
    }
}
