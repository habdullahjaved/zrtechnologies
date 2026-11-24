<?php

namespace App\Filament\Resources\RepairJobs\RelationManagers;

use App\Models\JobStatusChange;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only history of every status change on a job.
 */
class StatusHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistory';

    protected static ?string $title = 'Status history';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedClock;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('to_status')
                    ->label('Status')
                    ->badge()
                    ->description(fn (JobStatusChange $record): ?string => $record->from_status ? 'from '.$record->from_status->getLabel() : null),
                TextColumn::make('customer_message')
                    ->label('Message to customer')
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('note')
                    ->label('Internal note')
                    ->wrap()
                    ->placeholder('—'),
                IconColumn::make('customer_notified')
                    ->label('Emailed')
                    ->boolean(),
                TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('Customer / system'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('j M Y, H:i'),
            ])
            ->paginated([10, 25, 50]);
    }
}
