<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\PublishStatus;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')
                    ->description(fn (Page $record): string => str_replace(url('/'), '', $record->url()) ?: '/')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->state(fn (Page $record): string => match (true) {
                        $record->key?->isLegal() === true => 'Legal',
                        $record->isSystem() => 'Site page',
                        default => 'Custom',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Legal' => 'warning',
                        'Site page' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('needs_legal_review')
                    ->label('Legal review')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)
                    ->trueColor('warning')
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->falseColor('success'),
                TextColumn::make('word_count')
                    ->label('Words')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last changed')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PublishStatus::class),
                TernaryFilter::make('needs_legal_review')->label('Needs legal review'),
                TernaryFilter::make('key')
                    ->label('Type')
                    ->placeholder('All pages')
                    ->trueLabel('Site pages')
                    ->falseLabel('Custom pages')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('key'),
                        false: fn (Builder $query) => $query->whereNull('key'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Page $record): string => $record->url(), shouldOpenInNewTab: true)
                    ->visible(fn (Page $record): bool => $record->isLive()),
            ]);
    }
}
