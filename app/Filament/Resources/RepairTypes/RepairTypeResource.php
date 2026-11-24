<?php

namespace App\Filament\Resources\RepairTypes;

use App\Filament\NavigationGroup;
use App\Filament\Resources\RepairTypes\Pages\CreateRepairType;
use App\Filament\Resources\RepairTypes\Pages\EditRepairType;
use App\Filament\Resources\RepairTypes\Pages\ListRepairTypes;
use App\Filament\Resources\RepairTypes\Schemas\RepairTypeForm;
use App\Filament\Resources\RepairTypes\Tables\RepairTypesTable;
use App\Models\RepairType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RepairTypeResource extends Resource
{
    protected static ?string $model = RepairType::class;

    protected static ?string $modelLabel = 'repair type';

    protected static ?string $navigationLabel = 'Repair types';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalogue;

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return RepairTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepairTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRepairTypes::route('/'),
            'create' => CreateRepairType::route('/create'),
            'edit' => EditRepairType::route('/{record}/edit'),
        ];
    }
}
