<?php

namespace App\Filament\Resources\RepairJobs\Schemas;

use App\Filament\Resources\CrmFields;
use App\Filament\Resources\Leads\LeadActions;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\Address;
use App\Models\Customer;
use App\Models\RepairPrice;
use App\Models\User;
use App\Support\Money;
use App\Support\Phone;
use App\Support\Postcode;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class RepairJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Customer and collection')
                    ->columns(2)
                    ->columnSpan(2)
                    ->visible(fn (): bool => RepairJobResource::isOfficeUser())
                    ->schema([
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Customer $record): string => $record->full_name.($record->phone ? ' · '.Phone::display($record->phone) : ''))
                            ->searchable(['first_name', 'last_name', 'email', 'phone'])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('collection_address_id', null))
                            ->createOptionForm([
                                TextInput::make('first_name')->required()->maxLength(80),
                                TextInput::make('last_name')->maxLength(80),
                                CrmFields::phone()->requiredWithout('email'),
                                TextInput::make('email')->email()->maxLength(255)->requiredWithout('phone'),
                            ])
                            ->columnSpanFull(),
                        Select::make('collection_address_id')
                            ->label('Collection address')
                            ->options(fn (Get $get): array => self::addressOptions($get('customer_id')))
                            ->createOptionForm(self::addressFields())
                            ->createOptionUsing(fn (array $data, Get $get): int => self::createAddress($data, $get('customer_id')))
                            ->disabled(fn (Get $get): bool => blank($get('customer_id'))),
                        Select::make('return_address_id')
                            ->label('Return address')
                            ->placeholder('Same as collection')
                            ->options(fn (Get $get): array => self::addressOptions($get('customer_id')))
                            ->disabled(fn (Get $get): bool => blank($get('customer_id'))),
                        DatePicker::make('collection_date')
                            ->label('Collection date')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live(),
                        Select::make('availability_window_id')
                            ->label('Collection window')
                            ->options(fn (Get $get): array => LeadActions::windowOptions($get('collection_date'))),
                        Select::make('service_area_id')
                            ->label('Service area')
                            ->relationship('serviceArea', 'name')
                            ->preload(),
                        Select::make('technician_id')
                            ->label('Technician')
                            ->relationship('technician', 'name', fn (Builder $query) => $query->where('is_active', true)->whereHas('roles'))
                            ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->name)
                            ->preload(),
                    ]),

                Section::make('Device')
                    ->columns(2)
                    ->columnSpan(fn (): int => RepairJobResource::isOfficeUser() ? 1 : 2)
                    ->schema([
                        Select::make('device_model_id')
                            ->label('iPhone model')
                            ->relationship('deviceModel', 'name', fn (Builder $query) => $query->orderBy('sort'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (): bool => ! RepairJobResource::isOfficeUser())
                            ->columnSpanFull(),
                        TextInput::make('device_colour')->label('Colour')->maxLength(40),
                        TextInput::make('device_storage')->label('Storage')->maxLength(20),
                        TextInput::make('imei')
                            ->label('IMEI')
                            ->helperText('Encrypted. Dial *#06# to show it.')
                            ->maxLength(20)
                            ->rule('regex:/^[\d\s-]{14,20}$/'),
                        TextInput::make('serial_number')
                            ->label('Serial number')
                            ->maxLength(30),
                    ]),

                Section::make('Fault and diagnosis')
                    ->columnSpan(2)
                    ->schema([
                        Textarea::make('reported_fault')
                            ->label('What the customer reported')
                            ->rows(2)
                            ->maxLength(2000)
                            ->disabled(fn (): bool => ! RepairJobResource::isOfficeUser()),
                        Textarea::make('diagnosis')
                            ->label('Diagnosis')
                            ->helperText('What the technician found. Internal.')
                            ->rows(3)
                            ->maxLength(5000),
                        Textarea::make('customer_notes')
                            ->label('Customer’s notes')
                            ->rows(2)
                            ->maxLength(2000)
                            ->disabled(fn (): bool => ! RepairJobResource::isOfficeUser()),
                    ]),

                Section::make('Repairs and price')
                    ->columnSpan(3)
                    ->visible(fn (): bool => RepairJobResource::canViewFinancials())
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->orderColumn('sort')
                            ->defaultItems(0)
                            ->addActionLabel('Add repair')
                            ->columns(6)
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->schema([
                                Select::make('repair_type_id')
                                    ->label('Repair')
                                    ->relationship('repairType', 'name', fn (Builder $query) => $query->orderBy('sort'))
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::fillFromPriceList($get, $set))
                                    ->columnSpan(2),
                                Select::make('part_quality_id')
                                    ->label('Quality')
                                    ->relationship('partQuality', 'name')
                                    ->placeholder('Standard / n/a')
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::fillFromPriceList($get, $set)),
                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(160)
                                    ->columnSpan(2),
                                CrmFields::pounds('price_pence', 'Price')->required(),
                                TextInput::make('warranty_days')
                                    ->label('Warranty')
                                    ->suffix('days')
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(730)
                                    ->default(90)
                                    ->required(),
                            ]),
                        CrmFields::pounds('collection_fee_pence', 'Collection & return fee')->default(0),
                        CrmFields::pounds('final_price_pence', 'Final price')
                            ->helperText('Leave empty until the repair is done. Defaults to the quote plus fee when the job is completed.'),
                        Toggle::make('start_within_cancellation_period')
                            ->label('Customer asked us to start within the 14-day cancellation period'),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function addressOptions(mixed $customerId): array
    {
        if (blank($customerId)) {
            return [];
        }

        return Address::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('is_default')
            ->get()
            ->mapWithKeys(fn (Address $address): array => [$address->getKey() => $address->oneLine()])
            ->all();
    }

    /**
     * @return list<TextInput>
     */
    private static function addressFields(): array
    {
        return [
            TextInput::make('line1')->label('Address line 1')->required()->maxLength(120),
            TextInput::make('line2')->label('Address line 2')->maxLength(120),
            TextInput::make('town')->required()->maxLength(80),
            TextInput::make('postcode')->required()->maxLength(10),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function createAddress(array $data, mixed $customerId): int
    {
        $customer = Customer::query()->findOrFail($customerId);

        return $customer->addresses()->create([
            ...$data,
            'postcode' => Postcode::normalise((string) $data['postcode']) ?? $data['postcode'],
            'is_default' => ! $customer->addresses()->exists(),
        ])->getKey();
    }

    /**
     * Copy the price, warranty and wording from the price list for the job's model.
     */
    private static function fillFromPriceList(Get $get, Set $set): void
    {
        $repairTypeId = $get('repair_type_id');
        $modelId = $get('../../device_model_id');

        if (blank($repairTypeId) || blank($modelId)) {
            return;
        }

        $price = RepairPrice::query()
            ->with(['repairType', 'partQuality'])
            ->where('device_model_id', $modelId)
            ->where('repair_type_id', $repairTypeId)
            ->when(filled($get('part_quality_id')), fn (Builder $query) => $query->where('part_quality_id', $get('part_quality_id')))
            ->orderBy('part_quality_id')
            ->first();

        if ($price === null) {
            return;
        }

        $set('part_quality_id', $price->part_quality_id);
        $set('description', $price->repairType->name.($price->partQuality ? ' ('.$price->partQuality->name.')' : ''));
        $set('price_pence', Money::toPounds($price->effectivePricePence()));
        $set('warranty_days', $price->warranty_days);
    }
}
