<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'leads';

    protected static ?string $title = 'Enquiries';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedInboxStack;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->weight('bold'),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('channel')->label('Came from')->badge(),
                TextColumn::make('created_at')->label('Received')->date('j M Y'),
            ])
            ->recordActions([
                Action::make('open')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->visible(fn (Lead $record): bool => LeadResource::canView($record))
                    ->url(fn (Lead $record): string => LeadResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
