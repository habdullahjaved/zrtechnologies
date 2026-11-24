<?php

namespace App\Filament\Resources\Services\Tables;

use App\Models\Service;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('repairTypes'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->columns([
                ImageColumn::make('icon')
                    ->label('')
                    ->state(fn (Service $record): ?string => $record->icon ? asset("images/icons/icon-{$record->icon}-navy-96.webp") : null)
                    ->imageSize(32),
                TextColumn::make('name')
                    ->description(fn (Service $record): string => '/iphone-repair/'.$record->slug)
                    ->searchable(),
                TextColumn::make('repairTypes.name')
                    ->label('Repairs covered')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('word_count')
                    ->label('Words')
                    ->numeric()
                    ->color(fn (int $state): string => $state >= 300 ? 'success' : 'warning')
                    ->tooltip('Words of your own content on the page (aim for 300 or more).'),
                IconColumn::make('is_active')
                    ->label('Shown')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Service $record): string => $record->url(), shouldOpenInNewTab: true)
                    ->visible(fn (Service $record): bool => $record->is_active),
            ]);
    }
}
