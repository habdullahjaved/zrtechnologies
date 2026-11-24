<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Resources\CrmFields;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Contact')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(160)
                            ->required(),
                        TextInput::make('company_name')
                            ->label('Company')
                            ->maxLength(120),
                        CrmFields::phone()
                            ->requiredWithout('email')
                            ->live(onBlur: true)
                            ->hint(fn (?string $state, ?Lead $record) => CrmFields::duplicateCustomerHint($state, 'phone', $record?->customer))
                            ->hintColor('warning'),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->requiredWithout('phone')
                            ->live(onBlur: true)
                            ->hint(fn (?string $state, ?Lead $record) => CrmFields::duplicateCustomerHint($state, 'email', $record?->customer))
                            ->hintColor('warning'),
                        TextInput::make('postcode')
                            ->maxLength(10),
                        Select::make('device_model_id')
                            ->label('iPhone model')
                            ->relationship('deviceModel', 'name', fn (Builder $query) => $query->orderBy('sort'))
                            ->searchable()
                            ->preload(),
                        Select::make('repair_type_id')
                            ->label('Repair')
                            ->relationship('repairType', 'name', fn (Builder $query) => $query->orderBy('sort'))
                            ->preload(),
                        Textarea::make('message')
                            ->label('What they need')
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pipeline')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('type')
                            ->options(fn (string $operation): array => $operation === 'create'
                                ? LeadType::manualOptions()
                                : collect(LeadType::cases())->mapWithKeys(fn (LeadType $type): array => [$type->value => $type->getLabel()])->all())
                            ->default(LeadType::Manual->value)
                            ->disabled(fn (string $operation): bool => $operation !== 'create')
                            ->required(),
                        Select::make('channel')
                            ->label('Came from')
                            ->options(fn (string $operation): array => $operation === 'create'
                                ? Channel::manualOptions()
                                : collect(Channel::cases())->mapWithKeys(fn (Channel $channel): array => [$channel->value => $channel->getLabel()])->all())
                            ->default(Channel::Phone->value)
                            ->disabled(fn (string $operation): bool => $operation !== 'create')
                            ->helperText(fn (string $operation): ?string => $operation === 'create' ? null : 'Set automatically from the visitor’s source.')
                            ->required(),
                        Select::make('status')
                            ->options(LeadStatus::class)
                            ->default(LeadStatus::New->value)
                            ->live()
                            ->required(),
                        TextInput::make('lost_reason')
                            ->label('Why was it lost?')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => $get('status') === LeadStatus::Lost || $get('status') === LeadStatus::Lost->value)
                            ->required(fn (Get $get): bool => $get('status') === LeadStatus::Lost || $get('status') === LeadStatus::Lost->value),
                        Select::make('assigned_to')
                            ->label('Assigned to')
                            ->relationship('assignee', 'name', fn (Builder $query) => $query->where('is_active', true)->whereHas('roles'))
                            ->default(fn (): ?int => auth()->id())
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->name),
                        DateTimePicker::make('follow_up_at')
                            ->label('Follow up')
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),
                        CrmFields::pounds('estimated_value_pence', 'Estimated value'),
                    ]),
            ]);
    }
}
