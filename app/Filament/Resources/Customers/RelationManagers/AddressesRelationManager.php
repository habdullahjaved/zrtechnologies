<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\Address;
use App\Support\Postcode;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Addresses';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedMapPin;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('label')
                    ->placeholder('e.g. Home, Work')
                    ->maxLength(40),
                Toggle::make('is_default')
                    ->label('Default address')
                    ->inline(false),
                TextInput::make('line1')
                    ->label('Address line 1')
                    ->required()
                    ->maxLength(120),
                TextInput::make('line2')
                    ->label('Address line 2')
                    ->maxLength(120),
                TextInput::make('town')
                    ->required()
                    ->maxLength(80),
                TextInput::make('postcode')
                    ->required()
                    ->maxLength(10)
                    ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                        if (Postcode::normalise((string) $value) === null) {
                            $fail('Enter a full UK postcode, e.g. BB2 4NY.');
                        }
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('line1')
            ->columns([
                TextColumn::make('label')->placeholder('—'),
                TextColumn::make('line1')
                    ->label('Address')
                    ->state(fn (Address $record): string => $record->oneLine())
                    ->wrap(),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('directions')
                    ->icon(Heroicon::OutlinedMap)
                    ->iconButton()
                    ->tooltip('Directions')
                    ->color('gray')
                    ->url(fn (Address $record): string => $record->directionsUrl(), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
