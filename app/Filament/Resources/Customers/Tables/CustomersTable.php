<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Customer;
use App\Support\Phone;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['repairJobs', 'leads']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('first_name')
                    ->label('Name')
                    ->state(fn (Customer $record): string => $record->full_name)
                    ->description(fn (Customer $record): ?string => $record->company_name)
                    ->searchable(['first_name', 'last_name', 'company_name'])
                    ->sortable(['last_name', 'first_name']),
                TextColumn::make('phone')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? Phone::display($state) : null)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('phone', 'like', '%'.LeadResource::normalisePhoneSearch($search).'%'))
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('repair_jobs_count')
                    ->label('Jobs')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('leads_count')
                    ->label('Enquiries')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                IconColumn::make('marketing_consent')
                    ->label('Marketing')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('j M Y')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('marketing_consent')
                    ->label('Marketing emails'),
            ])
            ->recordActions([
                Action::make('call')
                    ->icon(Heroicon::OutlinedPhone)
                    ->iconButton()
                    ->tooltip('Call')
                    ->color('gray')
                    ->visible(fn (Customer $record): bool => filled($record->phone))
                    ->url(fn (Customer $record): string => Phone::telHref((string) $record->phone)),
                EditAction::make()->label('Open'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([25, 50, 100]);
    }
}
