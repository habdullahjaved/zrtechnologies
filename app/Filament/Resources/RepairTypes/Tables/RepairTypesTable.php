<?php

namespace App\Filament\Resources\RepairTypes\Tables;

use App\Filament\Resources\RepairPrices\RepairPriceResource;
use App\Models\RepairType;
use App\Services\Pricing\PricingService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RepairTypesTable
{
    public static function configure(Table $table): Table
    {
        $fromPrices = fn (): array => once(fn (): array => app(PricingService::class)->fromPrices());

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('prices'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->columns([
                ImageColumn::make('icon')
                    ->label('')
                    ->state(fn (RepairType $record): ?string => $record->icon ? asset("images/icons/icon-{$record->icon}-navy-96.webp") : null)
                    ->imageSize(32),
                TextColumn::make('name')
                    ->description(fn (RepairType $record): ?string => $record->summary ? str($record->summary)->limit(90)->toString() : null)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('from_price')
                    ->label('From')
                    ->state(fn (RepairType $record): ?string => isset($fromPrices()[$record->getKey()]) ? Money::format($fromPrices()[$record->getKey()]) : null)
                    ->placeholder('No prices'),
                TextColumn::make('prices_count')
                    ->label('Prices')
                    ->badge()
                    ->color('gray')
                    ->url(fn (RepairType $record): string => RepairPriceResource::getUrl('index', [
                        'filters' => ['repair_type_id' => ['values' => [$record->getKey()]]],
                    ])),
                IconColumn::make('requires_diagnosis')
                    ->label('After diagnosis')
                    ->boolean()
                    ->tooltip('Price confirmed after diagnosis ("from" price)'),
                IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean()
                    ->trueIcon(Heroicon::Star)
                    ->falseIcon(Heroicon::OutlinedStar)
                    ->trueColor('warning')
                    ->falseColor('gray'),
                IconColumn::make('is_active')
                    ->label('Offered')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Offered'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('prices')
                    ->label('Prices')
                    ->icon(Heroicon::OutlinedCurrencyPound)
                    ->color('gray')
                    ->visible(fn (): bool => RepairPriceResource::canViewAny())
                    ->url(fn (RepairType $record): string => RepairPriceResource::getUrl('index', [
                        'filters' => ['repair_type_id' => ['values' => [$record->getKey()]]],
                    ])),
            ]);
    }
}
