<?php

namespace App\Filament\Resources\RepairTypes\Schemas;

use App\Filament\Resources\CatalogueFields;
use App\Models\RepairType;
use App\Models\ServiceCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RepairTypeForm
{
    /**
     * Icons supplied in public/images/icons (icon-{key}-navy.png).
     *
     * @var array<string, string>
     */
    public const array ICONS = [
        'screen' => 'Screen',
        'battery' => 'Battery',
        'charging' => 'Charging port',
        'backglass' => 'Back glass',
        'camera' => 'Camera',
        'water' => 'Water damage',
        'speaker' => 'Speaker',
        'faceid' => 'Face ID',
        'data' => 'Data',
        'diagnostics' => 'Diagnostics',
        'buttons' => 'Buttons',
        'software' => 'Software',
    ];

    public static function configure(Schema $schema): Schema
    {
        $canManageOffer = fn (): bool => auth()->user()?->can('Create:RepairType') ?? false;

        return $schema->components([
            Section::make('Repair')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    CatalogueFields::name()
                        ->placeholder('e.g. Screen Replacement'),
                    CatalogueFields::slug(RepairType::class),
                    TextInput::make('short_name')
                        ->label('Short name')
                        ->helperText('Used where space is tight, e.g. "Screen".')
                        ->maxLength(40),
                    Select::make('service_category_id')
                        ->label('Service')
                        ->relationship('serviceCategory', 'name')
                        ->default(fn (): ?int => ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->value('id'))
                        ->required(),
                    Textarea::make('summary')
                        ->helperText('One or two sentences shown on repair cards and in the price checker. Up to 300 characters.')
                        ->rows(3)
                        ->maxLength(300)
                        ->columnSpanFull(),
                    Select::make('icon')
                        ->options(self::ICONS)
                        ->native(false),
                    TextInput::make('typical_duration_minutes')
                        ->label('Typical repair time')
                        ->suffix('minutes')
                        ->integer()
                        ->minValue(5)
                        ->maxValue(10080),
                ]),
            Section::make('Offer')
                ->description('Only managers can switch repairs on or off.')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_active')
                        ->label('Offered')
                        ->helperText('Switch off to hide this repair everywhere on the website (e.g. Face ID or data recovery if not offered).')
                        ->default(true)
                        ->disabled(fn (): bool => ! $canManageOffer()),
                    Toggle::make('requires_diagnosis')
                        ->label('Price confirmed after diagnosis')
                        ->helperText('Prices are shown as "from £…".')
                        ->disabled(fn (): bool => ! $canManageOffer()),
                    Toggle::make('is_popular')
                        ->label('Popular repair')
                        ->helperText('Featured on the home page.'),
                    TextInput::make('sort')
                        ->label('Sort order')
                        ->integer()
                        ->minValue(0)
                        ->default(fn (): int => (int) RepairType::query()->max('sort') + 10)
                        ->required(),
                ]),
        ]);
    }
}
