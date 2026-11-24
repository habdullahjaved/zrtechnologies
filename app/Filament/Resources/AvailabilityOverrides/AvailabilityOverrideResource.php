<?php

namespace App\Filament\Resources\AvailabilityOverrides;

use App\Filament\NavigationGroup;
use App\Filament\Resources\AvailabilityOverrides\Pages\ManageAvailabilityOverrides;
use App\Models\AvailabilityOverride;
use App\Models\AvailabilityWindow;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * One-off changes to a window's capacity on a particular date.
 */
class AvailabilityOverrideResource extends Resource
{
    protected static ?string $model = AvailabilityOverride::class;

    protected static ?string $modelLabel = 'capacity change';

    protected static ?string $navigationLabel = 'Capacity changes';

    protected static ?string $slug = 'capacity-changes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Coverage;

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->minDate(fn (string $operation): ?Carbon => $operation === 'create' ? today() : null)
                ->live()
                ->required(),
            Select::make('availability_window_id')
                ->label('Window')
                ->options(fn (Get $get): array => AvailabilityWindow::query()
                    ->when(filled($get('date')), fn (Builder $query) => $query->where('weekday', Carbon::parse((string) $get('date'))->isoWeekday()))
                    ->orderBy('weekday')
                    ->orderBy('starts_at')
                    ->get()
                    ->mapWithKeys(fn (AvailabilityWindow $window): array => [
                        $window->getKey() => $window->weekdayName().' · '.$window->label.' ('.$window->timeRange().')',
                    ])
                    ->all())
                ->helperText('Pick a date first to see that day’s windows.')
                ->required()
                ->rules([
                    fn (Get $get, ?AvailabilityOverride $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        $date = $get('date');

                        if (blank($date)) {
                            return;
                        }

                        $exists = AvailabilityOverride::query()
                            ->whereDate('date', Carbon::parse((string) $date))
                            ->where('availability_window_id', $value)
                            ->when($record, fn (Builder $query) => $query->whereKeyNot($record?->getKey()))
                            ->exists();

                        if ($exists) {
                            $fail('There is already a capacity change for this window on this date.');
                        }
                    },
                ]),
            TextInput::make('capacity')
                ->label('Collections')
                ->helperText('0 closes this window for the day.')
                ->integer()
                ->minValue(0)
                ->maxValue(50)
                ->required(),
            TextInput::make('note')
                ->placeholder('e.g. Technician on training')
                ->maxLength(200),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('availabilityWindow'))
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')
                    ->date('D d/m/Y')
                    ->sortable(),
                TextColumn::make('availabilityWindow.label')
                    ->label('Window')
                    ->formatStateUsing(fn (AvailabilityOverride $record): string => $record->availabilityWindow->label.' ('.$record->availabilityWindow->timeRange().')'),
                TextColumn::make('capacity')
                    ->label('Collections')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Closed' : (string) $state)
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'info'),
                TextColumn::make('note')
                    ->placeholder('—'),
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
            ])
            ->emptyStateHeading('No capacity changes')
            ->emptyStateDescription('Use these to take fewer (or more) collections in a window on a particular day.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAvailabilityOverrides::route('/'),
        ];
    }
}
