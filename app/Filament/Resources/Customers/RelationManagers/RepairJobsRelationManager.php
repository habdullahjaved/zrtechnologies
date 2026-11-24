<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RepairJobsRelationManager extends RelationManager
{
    protected static string $relationship = 'repairJobs';

    protected static ?string $title = 'Repair jobs';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedWrenchScrewdriver;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('deviceModel'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->weight('bold'),
                TextColumn::make('deviceModel.name')->label('Device')->placeholder('—'),
                TextColumn::make('reported_fault')->label('Fault')->limit(40)->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Booked')->date('j M Y'),
            ])
            ->recordActions([
                Action::make('open')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->visible(fn (RepairJob $record): bool => RepairJobResource::canView($record))
                    ->url(fn (RepairJob $record): string => RepairJobResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
