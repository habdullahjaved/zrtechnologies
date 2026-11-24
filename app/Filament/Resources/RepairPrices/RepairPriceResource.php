<?php

namespace App\Filament\Resources\RepairPrices;

use App\Actions\Pricing\AdjustRepairPrices;
use App\Filament\NavigationGroup;
use App\Filament\Resources\RepairPrices\Pages\ManageRepairPrices;
use App\Models\DeviceModel;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Support\Money;
use BackedEnum;
use Closure;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The price list: every model × repair × part quality, editable inline, with
 * CSV import/export and a bulk percentage change.
 */
class RepairPriceResource extends Resource
{
    protected static ?string $model = RepairPrice::class;

    protected static ?string $modelLabel = 'price';

    protected static ?string $navigationLabel = 'Price list';

    protected static ?string $slug = 'prices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyPound;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalogue;

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        $drafts = RepairPrice::query()->where('is_draft', true)->count();

        return $drafts > 0 ? number_format($drafts).' draft' : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Draft prices must be confirmed before launch';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    Select::make('device_model_id')
                        ->label('Model')
                        ->relationship('deviceModel', 'name', fn (Builder $query) => $query->orderBy('sort'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('repair_type_id')
                        ->label('Repair')
                        ->relationship('repairType', 'name', fn (Builder $query) => $query->orderBy('sort'))
                        ->preload()
                        ->required()
                        ->rules([
                            // Checked here rather than on the optional part quality, which may be empty.
                            fn (Get $get, ?RepairPrice $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                $exists = RepairPrice::query()
                                    ->where('device_model_id', $get('device_model_id'))
                                    ->where('repair_type_id', $value)
                                    ->where('part_quality_key', (int) $get('part_quality_id'))
                                    ->when($record, fn (Builder $query) => $query->whereKeyNot($record?->getKey()))
                                    ->exists();

                                if ($exists) {
                                    $fail('This model already has a price for this repair and part quality.');
                                }
                            },
                        ]),
                    Select::make('part_quality_id')
                        ->label('Part quality')
                        ->relationship('partQuality', 'name', fn (Builder $query) => $query->orderBy('sort'))
                        ->placeholder('Not applicable')
                        ->helperText('Only for repairs offered with a choice of parts.'),
                ]),
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    static::poundsInput('price_pence')
                        ->label('Price')
                        ->required(),
                    static::poundsInput('sale_price_pence')
                        ->label('Sale price')
                        ->lt('price_pence')
                        ->validationMessages(['lt' => 'The sale price must be lower than the price.'])
                        ->helperText('Optional. Shown with the normal price crossed out.'),
                    TextInput::make('warranty_days')
                        ->label('Warranty')
                        ->suffix('days')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(1095)
                        ->default(90)
                        ->helperText('365 = 12 months. 0 = no warranty.'),
                ]),
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_active')
                        ->label('Show on the website')
                        ->default(true),
                    Toggle::make('in_stock')
                        ->label('Part in stock')
                        ->default(true),
                    Toggle::make('is_draft')
                        ->label('Draft – confirm before launch')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $cannotEdit = fn (RepairPrice $record): bool => ! static::canEdit($record);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['deviceModel', 'repairType', 'partQuality']))
            ->defaultSort(fn (Builder $query) => $query
                ->orderBy(DeviceModel::query()->select('sort')->whereColumn('device_models.id', 'repair_prices.device_model_id'))
                ->orderBy(RepairType::query()->select('sort')->whereColumn('repair_types.id', 'repair_prices.repair_type_id'))
                ->orderBy('part_quality_key'))
            ->groups([
                Group::make('deviceModel.name')->label('Model')->collapsible(),
                Group::make('repairType.name')->label('Repair')->collapsible(),
            ])
            ->columns([
                TextColumn::make('deviceModel.name')
                    ->label('Model')
                    ->searchable(),
                TextColumn::make('repairType.name')
                    ->label('Repair')
                    ->description(fn (RepairPrice $record): ?string => $record->partQuality?->name)
                    ->searchable(),
                TextInputColumn::make('price_pounds')
                    ->label('Price (£)')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['required', 'numeric', 'min:0', 'max:5000'])
                    ->disabled($cannotEdit)
                    ->width('8rem'),
                TextInputColumn::make('sale_price_pounds')
                    ->label('Sale (£)')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:5000'])
                    ->disabled($cannotEdit)
                    ->width('8rem')
                    ->toggleable(),
                TextColumn::make('warranty_days')
                    ->label('Warranty')
                    ->formatStateUsing(fn (RepairPrice $record): string => $record->warrantyLabel() ?? 'None')
                    ->sortable()
                    ->toggleable(),
                ToggleColumn::make('in_stock')
                    ->label('In stock')
                    ->disabled($cannotEdit)
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label('On website')
                    ->disabled($cannotEdit),
                TextColumn::make('is_draft')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'DRAFT – confirm before launch' : 'Confirmed')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success'),
                TextColumn::make('updated_at')
                    ->label('Last changed')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('device_model_id')
                    ->label('Model')
                    ->relationship('deviceModel', 'name', fn (Builder $query) => $query->orderBy('sort'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('repair_type_id')
                    ->label('Repair')
                    ->relationship('repairType', 'name', fn (Builder $query) => $query->orderBy('sort'))
                    ->preload()
                    ->multiple(),
                SelectFilter::make('part_quality_id')
                    ->label('Part quality')
                    ->relationship('partQuality', 'name'),
                TernaryFilter::make('is_draft')
                    ->label('Status')
                    ->trueLabel('Drafts only')
                    ->falseLabel('Confirmed only'),
                TernaryFilter::make('is_active')
                    ->label('On website'),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make()
                    ->modalHeading(fn (RepairPrice $record): string => 'Edit price: '.$record->label()),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('confirm')
                        ->label('Confirm prices')
                        ->icon(Heroicon::OutlinedCheckBadge)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('The selected prices will no longer be marked as drafts.')
                        ->authorize(fn (): bool => static::canUpdateAny())
                        ->action(fn (Collection $records) => self::setDraft($records, false))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('markDraft')
                        ->label('Mark as draft')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->authorize(fn (): bool => static::canUpdateAny())
                        ->action(fn (Collection $records) => self::setDraft($records, true))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('adjust')
                        ->label('Change by %')
                        ->icon(Heroicon::OutlinedArrowTrendingUp)
                        ->modalHeading('Change selected prices by a percentage')
                        ->modalDescription('Sale prices are not changed. Every change is recorded in the audit log.')
                        ->schema([
                            TextInput::make('percent')
                                ->label('Change')
                                ->suffix('%')
                                ->helperText('Use a minus sign to reduce prices, e.g. -10.')
                                ->required()
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(200)
                                ->notIn(['0']),
                            Radio::make('rounding')
                                ->label('Round the new prices')
                                ->options(AdjustRepairPrices::ROUNDING_OPTIONS)
                                ->default(AdjustRepairPrices::ROUND_NINE)
                                ->required(),
                        ])
                        ->authorize(fn (): bool => static::canUpdateAny())
                        ->action(
                            function (Collection $records, array $data): void {
                                $prices = RepairPrice::query()->whereKey($records->modelKeys())->get();
                                $changed = app(AdjustRepairPrices::class)->handle($prices, (float) $data['percent'], (string) $data['rounding']);

                                Notification::make()
                                    ->title("{$changed} ".str('price')->plural($changed).' changed')
                                    ->success()
                                    ->send();
                            })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([25, 50, 100, 250])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('No prices yet')
            ->emptyStateDescription('Add a price, or import a CSV file.');
    }

    /**
     * A pounds-and-pence input bound to an integer pence attribute.
     */
    public static function poundsInput(string $attribute): TextInput
    {
        return TextInput::make($attribute)
            ->prefix('£')
            ->numeric()
            ->minValue(0)
            ->maxValue(5000)
            ->step(0.01)
            ->formatStateUsing(fn (mixed $state): ?string => filled($state) ? Money::toPounds((int) $state) : null)
            ->dehydrateStateUsing(fn (mixed $state): ?int => filled($state) ? Money::parsePounds($state) : null);
    }

    /**
     * Can the signed-in user edit prices in general (for bulk actions)?
     */
    public static function canUpdateAny(): bool
    {
        return auth()->user()?->can('Update:RepairPrice') ?? false;
    }

    /**
     * @param  Collection<int, Model>  $records
     */
    private static function setDraft(Collection $records, bool $isDraft): void
    {
        $changed = 0;

        foreach ($records as $record) {
            if ($record instanceof RepairPrice && $record->is_draft !== $isDraft) {
                $record->update(['is_draft' => $isDraft]);
                $changed++;
            }
        }

        Notification::make()
            ->title($isDraft
                ? "{$changed} ".str('price')->plural($changed).' marked as draft'
                : "{$changed} ".str('price')->plural($changed).' confirmed')
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRepairPrices::route('/'),
        ];
    }

    public static function canGloballySearch(): bool
    {
        return false;
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof RepairPrice ? $record->label() : 'price';
    }
}
