<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Resources\Leads\LeadActions;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Support\Money;
use App\Support\Phone;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['assignee', 'deviceModel', 'repairJob']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Lead $record): string => $record->created_at?->format('j M, H:i') ?? ''),
                TextColumn::make('name')
                    ->label('Contact')
                    ->state(fn (Lead $record): string => $record->displayName())
                    ->description(fn (Lead $record): ?string => $record->phone ? Phone::display($record->phone) : $record->email)
                    ->searchable(['name', 'email', 'phone', 'postcode'], query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                        $phone = LeadResource::normalisePhoneSearch($search);

                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$phone}%")
                            ->orWhere('postcode', 'like', "%{$search}%");
                    })),
                TextColumn::make('type')
                    ->badge()
                    ->description(fn (Lead $record): ?string => $record->is_partial ? 'Not finished' : null),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('channel')
                    ->label('Came from')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('deviceModel.name')
                    ->label('Device')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('estimated_value_pence')
                    ->label('Value')
                    ->formatStateUsing(fn (?int $state): ?string => $state !== null ? Money::format($state) : null)
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('assignee.name')
                    ->label('Assigned')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                TextColumn::make('follow_up_at')
                    ->label('Follow up')
                    ->since()
                    ->dateTimeTooltip('j M Y, H:i')
                    ->color(fn (Lead $record): ?string => $record->follow_up_at?->isPast() && $record->status->isOpen() ? 'danger' : null)
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LeadStatus::class)
                    ->multiple()
                    ->default([LeadStatus::New->value, LeadStatus::Contacted->value, LeadStatus::Quoted->value]),
                SelectFilter::make('type')
                    ->options(LeadType::class)
                    ->multiple(),
                SelectFilter::make('channel')
                    ->label('Came from')
                    ->options(Channel::class)
                    ->multiple(),
                SelectFilter::make('assigned_to')
                    ->label('Assigned to')
                    ->relationship('assignee', 'name'),
                TernaryFilter::make('is_partial')
                    ->label('Unfinished bookings'),
                Filter::make('follow_up_due')
                    ->label('Follow-up due')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', LeadStatus::open())->where('follow_up_at', '<=', now())),
                Filter::make('mine')
                    ->label('Assigned to me')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('assigned_to', auth()->id())),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    LeadActions::call(),
                    LeadActions::whatsApp(),
                    LeadActions::email(),
                    LeadActions::changeStatus(),
                    LeadActions::assignToMe(),
                    LeadActions::convertToJob(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No leads match')
            ->emptyStateDescription('New bookings and enquiries from the website appear here.')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
