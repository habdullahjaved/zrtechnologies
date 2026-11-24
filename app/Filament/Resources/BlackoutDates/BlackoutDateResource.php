<?php

namespace App\Filament\Resources\BlackoutDates;

use App\Filament\NavigationGroup;
use App\Filament\Resources\BlackoutDates\Pages\ManageBlackoutDates;
use App\Models\BlackoutDate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Whole days with no collections: bank holidays, holidays, training days.
 */
class BlackoutDateResource extends Resource
{
    protected static ?string $model = BlackoutDate::class;

    protected static ?string $modelLabel = 'closed day';

    protected static ?string $navigationLabel = 'Closed days';

    protected static ?string $slug = 'closed-days';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Coverage;

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->unique(ignoreRecord: true)
                ->validationMessages(['unique' => 'This day is already closed.'])
                ->required(),
            TextInput::make('reason')
                ->placeholder('e.g. Christmas Day')
                ->required()
                ->maxLength(200),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')
                    ->date('D d/m/Y')
                    ->sortable(),
                TextColumn::make('reason'),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->query(fn (Builder $query) => $query->whereDate('date', '>=', today()))
                    ->default(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof BlackoutDate ? $record->date->format('d/m/Y').' ('.$record->reason.')' : 'closed day';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBlackoutDates::route('/'),
        ];
    }
}
