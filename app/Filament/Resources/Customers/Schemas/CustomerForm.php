<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Filament\Resources\CrmFields;
use App\Models\Customer;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Contact details')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('last_name')
                            ->maxLength(80),
                        CrmFields::phone()
                            ->requiredWithout('email')
                            ->live(onBlur: true)
                            ->hint(fn (?string $state, ?Customer $record) => CrmFields::duplicateCustomerHint($state, 'phone', $record))
                            ->hintColor('warning'),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->requiredWithout('phone')
                            ->live(onBlur: true)
                            ->hint(fn (?string $state, ?Customer $record) => CrmFields::duplicateCustomerHint($state, 'email', $record))
                            ->hintColor('warning'),
                        CrmFields::phone('phone_alt', 'Other phone'),
                        TextInput::make('company_name')
                            ->label('Company')
                            ->maxLength(120),
                        Textarea::make('notes')
                            ->label('About this customer')
                            ->helperText('Long-term notes, e.g. preferred contact times. Use the Notes tab for call notes.')
                            ->rows(3)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Marketing')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('marketing_consent')
                            ->label('Happy to receive marketing emails')
                            ->helperText(fn (?Customer $record): string => $record?->marketing_consent_at
                                ? 'Last changed '.$record->marketing_consent_at->format('j M Y').' ('.str($record->marketing_consent_source ?? 'unknown')->replace('_', ' ').').'
                                : 'Only switch on if the customer has clearly agreed.'),
                    ]),
            ]);
    }
}
