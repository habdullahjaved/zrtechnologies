<?php

namespace App\Filament\Resources\ServiceCategories;

use App\Enums\FormType;
use App\Filament\NavigationGroup;
use App\Filament\Resources\CatalogueFields;
use App\Filament\Resources\ServiceCategories\Pages\ManageServiceCategories;
use App\Models\ServiceCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Lines of business. Only iPhone Repair is live at launch; the others are
 * ready to switch on as the business grows.
 */
class ServiceCategoryResource extends Resource
{
    protected static ?string $model = ServiceCategory::class;

    protected static ?string $modelLabel = 'service';

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalogue;

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            CatalogueFields::name(),
            CatalogueFields::slug(ServiceCategory::class),
            Select::make('form_type')
                ->label('How customers ask for it')
                ->options(FormType::class)
                ->required(),
            Toggle::make('is_active')
                ->label('Live on the website')
                ->inline(false),
            Textarea::make('description')
                ->rows(3)
                ->maxLength(1000)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('repairTypes'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->description(fn (ServiceCategory $record): ?string => $record->description)
                    ->wrap(),
                TextColumn::make('form_type')
                    ->label('Form')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('repair_types_count')
                    ->label('Repair types')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Live')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (ServiceCategory $record): bool => $record->repair_types_count > 0)
                    ->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageServiceCategories::route('/'),
        ];
    }
}
