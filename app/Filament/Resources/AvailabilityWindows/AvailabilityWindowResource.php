<?php

namespace App\Filament\Resources\AvailabilityWindows;

use App\Filament\NavigationGroup;
use App\Filament\Resources\AvailabilityWindows\Pages\ManageAvailabilityWindows;
use App\Models\AvailabilityWindow;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The weekly collection timetable customers book from.
 */
class AvailabilityWindowResource extends Resource
{
    protected static ?string $model = AvailabilityWindow::class;

    protected static ?string $modelLabel = 'collection window';

    protected static ?string $navigationLabel = 'Collection times';

    protected static ?string $slug = 'collection-times';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Coverage;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('weekday')
                ->label('Day')
                ->options(AvailabilityWindow::WEEKDAYS)
                ->required(),
            TextInput::make('label')
                ->placeholder('e.g. Morning')
                ->required()
                ->maxLength(60),
            TimePicker::make('starts_at')
                ->label('From')
                ->seconds(false)
                ->required(),
            TimePicker::make('ends_at')
                ->label('Until')
                ->seconds(false)
                ->after('starts_at')
                ->validationMessages(['after' => 'The window must end after it starts.'])
                ->required(),
            TextInput::make('capacity')
                ->label('Collections per window')
                ->helperText('How many collections fit in this window. Booked collections count against it once bookings go live.')
                ->integer()
                ->minValue(1)
                ->maxValue(50)
                ->default(3)
                ->required(),
            Toggle::make('is_active')
                ->label('Bookable')
                ->default(true)
                ->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderBy('weekday')->orderBy('starts_at'))
            ->defaultGroup(
                Group::make('weekday')
                    ->label('Day')
                    ->getTitleFromRecordUsing(fn (AvailabilityWindow $record): string => $record->weekdayName())
                    ->titlePrefixedWithLabel(false),
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('starts_at')
                    ->label('Time')
                    ->formatStateUsing(fn (AvailabilityWindow $record): string => $record->timeRange()),
                TextColumn::make('capacity')
                    ->label('Collections'),
                IconColumn::make('is_active')
                    ->label('Bookable')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('weekday')
                    ->label('Day')
                    ->options(AvailabilityWindow::WEEKDAYS),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->iconButton(),
            ]);
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof AvailabilityWindow
            ? $record->weekdayName().' '.$record->label.' ('.$record->timeRange().')'
            : 'collection window';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAvailabilityWindows::route('/'),
        ];
    }
}
