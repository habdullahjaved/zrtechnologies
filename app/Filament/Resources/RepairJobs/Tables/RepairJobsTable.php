<?php

namespace App\Filament\Resources\RepairJobs\Tables;

use App\Enums\JobStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\RepairJobs\RepairJobActions;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use App\Support\Phone;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RepairJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'deviceModel', 'technician', 'collectionAddress', 'returnAddress']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->weight('bold')
                    ->sortable()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                        $digits = preg_replace('/\D/', '', $search) ?? '';

                        $query->where('reference', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn (Builder $customer) => $customer
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', '%'.LeadResource::normalisePhoneSearch($search).'%'));

                        if (strlen($digits) >= 14) {
                            $query->orWhere('imei_hash', RepairJob::hashImei($digits));
                        }
                    }))
                    ->description(fn (RepairJob $record): string => $record->deviceModel->name ?? 'Device not set'),
                TextColumn::make('customer.first_name')
                    ->label('Customer')
                    ->state(fn (RepairJob $record): string => $record->customer->full_name)
                    ->description(fn (RepairJob $record): ?string => $record->customer->phone ? Phone::display($record->customer->phone) : null),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('collection_date')
                    ->label('Collection')
                    ->date('D j M')
                    ->description(fn (RepairJob $record): ?string => $record->collection_window)
                    ->color(fn (RepairJob $record): ?string => $record->status->holdsCollectionSlot() && $record->collection_date?->isPast() && ! $record->collection_date->isToday() ? 'danger' : null)
                    ->placeholder('Not booked')
                    ->sortable(),
                TextColumn::make('collectionAddress.postcode')
                    ->label('Postcode')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('technician.name')
                    ->label('Technician')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                TextColumn::make('estimated_total')
                    ->label('Estimate')
                    ->state(fn (RepairJob $record): string => $record->estimatedTotalLabel())
                    ->visible(fn (): bool => RepairJobResource::canViewFinancials())
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Booked')
                    ->since()
                    ->dateTimeTooltip('j M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(JobStatus::class)
                    ->multiple()
                    ->default(array_map(fn (JobStatus $status): string => $status->value, JobStatus::active())),
                Filter::make('collection_date')
                    ->schema([
                        DatePicker::make('collection_on')
                            ->label('Collection on')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['collection_on'] ?? null,
                        fn (Builder $query, string $date): Builder => $query->whereDate('collection_date', $date),
                    ))
                    ->indicateUsing(fn (array $data): ?string => filled($data['collection_on'] ?? null) ? 'Collection on '.now()->parse($data['collection_on'])->format('j M') : null),
                Filter::make('collections_today')
                    ->label('Collections today')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', JobStatus::holdingSlot())->whereDate('collection_date', today())),
                SelectFilter::make('technician_id')
                    ->label('Technician')
                    ->relationship('technician', 'name')
                    ->visible(fn (): bool => RepairJobResource::isOfficeUser()),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    RepairJobActions::changeStatus(),
                    RepairJobActions::assignTechnician(),
                    RepairJobActions::call(),
                    RepairJobActions::whatsApp(),
                    RepairJobActions::directions(),
                    EditAction::make(),
                ]),
            ])
            ->emptyStateHeading('No repair jobs match')
            ->emptyStateDescription('Online bookings and leads converted to jobs appear here.')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
