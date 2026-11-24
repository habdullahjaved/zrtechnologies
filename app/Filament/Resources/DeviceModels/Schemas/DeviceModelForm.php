<?php

namespace App\Filament\Resources\DeviceModels\Schemas;

use App\Enums\DisplayType;
use App\Filament\Resources\CatalogueFields;
use App\Filament\Resources\ContentFields;
use App\Models\DeviceBrand;
use App\Models\DeviceModel;
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
use Illuminate\Support\Str;

class DeviceModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Model')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    CatalogueFields::name()
                        ->placeholder('e.g. iPhone 17 Pro'),
                    CatalogueFields::slug(DeviceModel::class),
                    Select::make('device_brand_id')
                        ->label('Brand')
                        ->relationship('brand', 'name')
                        ->default(fn (): ?int => DeviceBrand::query()->where('slug', DeviceBrand::APPLE)->value('id'))
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')->required()->maxLength(100),
                        ])
                        ->createOptionUsing(fn (array $data): int => DeviceBrand::query()->create([
                            'name' => $data['name'],
                            'slug' => Str::slug($data['name']),
                        ])->getKey()),
                    TextInput::make('family')
                        ->helperText('Groups related models, e.g. "iPhone 17" for the 17, 17 Pro and 17 Pro Max.')
                        ->maxLength(60),
                    TextInput::make('release_year')
                        ->label('Release year')
                        ->integer()
                        ->minValue(2007)
                        ->maxValue((int) now()->addYear()->format('Y')),
                    Select::make('display_type')
                        ->label('Screen type')
                        ->options(DisplayType::class),
                    Textarea::make('notes')
                        ->label('Model notes')
                        ->helperText('Model-specific details for customers, e.g. which parts this model uses. Shown in the "About" box on the model page.')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ]),
            Section::make('On the website')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_active')
                        ->label('Show on the website')
                        ->helperText('Hidden models disappear from the price checker and booking.')
                        ->default(true),
                    Toggle::make('is_popular')
                        ->label('Popular model')
                        ->helperText('Listed first in pickers.'),
                    TextInput::make('sort')
                        ->label('Sort order')
                        ->helperText('Lower numbers come first.')
                        ->integer()
                        ->minValue(0)
                        ->default(fn (): int => max(0, (int) DeviceModel::query()->min('sort') - 10))
                        ->required(),
                ]),
            Section::make('Model page')
                ->description('The page at /iphone-repair/{slug}. Its price list, model facts and booking buttons are added automatically.')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('intro')
                        ->label('Introduction')
                        ->helperText('Shown under the page heading. Leave empty for the standard wording.')
                        ->rows(3)
                        ->maxLength(1000),
                    ContentFields::blocks(),
                    ContentFields::faqs(),
                ]),
            ContentFields::seo(
                defaultTitle: fn (Get $get): string => app(SeoResolver::class)->pattern(app(SiteSettings::class)->title_pattern_model, [
                    'model' => $get('../name') ?: 'iPhone',
                    'price' => '£…',
                ]),
                defaultDescription: fn (Get $get): ?string => filled($get('../name')) ? $get('../name').' repair in Blackburn: screen, battery, charging port, cameras and more.' : null,
                url: fn (?Model $record): ?string => $record instanceof DeviceModel ? $record->url() : null,
                contentText: fn (Get $get): string => implode(' ', [$get('../notes'), $get('../intro'), ContentFields::blocksText($get('../blocks'))]),
            ),
        ]);
    }
}
