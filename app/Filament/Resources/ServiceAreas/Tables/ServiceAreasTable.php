<?php

namespace App\Filament\Resources\ServiceAreas\Tables;

use App\Models\ServiceArea;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServiceAreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->description(fn (ServiceArea $record): ?string => $record->summary)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('postcode_districts')
                    ->label('Districts')
                    ->badge()
                    ->color('info'),
                TextColumn::make('radius_km')
                    ->label('Radius')
                    ->suffix(' km')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('collection_fee_pence')
                    ->label('Fee')
                    ->formatStateUsing(fn (ServiceArea $record): string => $record->collectionFeeLabel()),
                IconColumn::make('is_active')
                    ->label('Collecting')
                    ->boolean(),
                TextColumn::make('is_draft')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'DRAFT – confirm before launch' : 'Confirmed')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Collecting'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
