<?php

namespace App\Filament\Resources\DeviceModels\Tables;

use App\Enums\DisplayType;
use App\Filament\Resources\RepairPrices\RepairPriceResource;
use App\Models\DeviceModel;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeviceModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'prices',
                'prices as draft_prices_count' => fn (Builder $prices) => $prices->where('is_draft', true),
            ]))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')
                    ->description(fn (DeviceModel $record): ?string => $record->family)
                    ->searchable(),
                TextColumn::make('release_year')
                    ->label('Year')
                    ->sortable(),
                TextColumn::make('display_type')
                    ->label('Screen')
                    ->badge()
                    ->formatStateUsing(fn (DisplayType $state): string => strtoupper($state->value))
                    ->color('gray'),
                TextColumn::make('prices_count')
                    ->label('Prices')
                    ->badge()
                    ->color('gray')
                    ->url(fn (DeviceModel $record): string => RepairPriceResource::getUrl('index', [
                        'filters' => ['device_model_id' => ['values' => [$record->getKey()]]],
                    ])),
                TextColumn::make('draft_prices_count')
                    ->label('Drafts')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'success')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? (string) $state : 'None'),
                IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean()
                    ->trueIcon(Heroicon::Star)
                    ->falseIcon(Heroicon::OutlinedStar)
                    ->trueColor('warning')
                    ->falseColor('gray'),
                IconColumn::make('is_active')
                    ->label('On website')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('family')
                    ->options(fn (): array => DeviceModel::query()->whereNotNull('family')->orderBy('sort')->pluck('family', 'family')->all()),
                SelectFilter::make('display_type')
                    ->label('Screen')
                    ->options(DisplayType::class),
                TernaryFilter::make('is_active')
                    ->label('On website'),
                TernaryFilter::make('is_popular')
                    ->label('Popular'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('prices')
                    ->label('Prices')
                    ->icon(Heroicon::OutlinedCurrencyPound)
                    ->color('gray')
                    ->visible(fn (): bool => RepairPriceResource::canViewAny())
                    ->url(fn (DeviceModel $record): string => RepairPriceResource::getUrl('index', [
                        'filters' => ['device_model_id' => ['values' => [$record->getKey()]]],
                    ])),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
