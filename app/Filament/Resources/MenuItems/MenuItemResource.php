<?php

namespace App\Filament\Resources\MenuItems;

use App\Enums\MenuLocation;
use App\Filament\NavigationGroup;
use App\Filament\Resources\MenuItems\Pages\ManageMenuItems;
use App\Models\MenuItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Header and footer links. The "iPhone Repair" menu and the footer repair and
 * area lists are built from the catalogue automatically.
 */
class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $modelLabel = 'menu link';

    protected static ?string $navigationLabel = 'Menus';

    protected static ?string $slug = 'menus';

    protected static ?string $recordTitleAttribute = 'label';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('menu')
                ->options(MenuLocation::class)
                ->required()
                ->native(false),
            TextInput::make('label')
                ->required()
                ->maxLength(80),
            TextInput::make('url')
                ->label('Link')
                ->helperText('A path on this site, e.g. /how-it-works, or a full web address.')
                ->required()
                ->maxLength(500)
                ->columnSpanFull(),
            Toggle::make('opens_in_new_tab')
                ->label('Open in a new tab'),
            Toggle::make('is_active')
                ->label('Show')
                ->default(true),
            TextInput::make('sort')
                ->label('Sort order')
                ->integer()
                ->minValue(0)
                ->default(fn (): int => (int) MenuItem::query()->max('sort') + 10)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->defaultGroup(Group::make('menu')->titlePrefixedWithLabel(false))
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('url')
                    ->label('Link')
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Shown')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('menu')->options(MenuLocation::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMenuItems::route('/'),
        ];
    }
}
