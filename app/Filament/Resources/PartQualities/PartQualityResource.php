<?php

namespace App\Filament\Resources\PartQualities;

use App\Filament\NavigationGroup;
use App\Filament\Resources\CatalogueFields;
use App\Filament\Resources\PartQualities\Pages\ManagePartQualities;
use App\Models\PartQuality;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PartQualityResource extends Resource
{
    protected static ?string $model = PartQuality::class;

    protected static ?string $modelLabel = 'part quality';

    protected static ?string $pluralModelLabel = 'part qualities';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalogue;

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            CatalogueFields::name()
                ->maxLength(60)
                ->placeholder('e.g. Premium'),
            CatalogueFields::slug(PartQuality::class),
            Textarea::make('description')
                ->helperText('Explains the difference to customers when they choose a part. Up to 300 characters.')
                ->rows(3)
                ->maxLength(300)
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Offered')
                ->helperText('Switch off to hide every price that uses this quality.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('prices'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->description(fn (PartQuality $record): ?string => $record->description)
                    ->wrap(),
                TextColumn::make('prices_count')
                    ->label('Prices')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Offered')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (PartQuality $record): bool => $record->prices_count > 0)
                    ->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePartQualities::route('/'),
        ];
    }
}
