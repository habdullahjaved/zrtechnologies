<?php

namespace App\Filament\Resources\RepairJobs\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use App\Support\Money;
use App\Support\Phone;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RepairJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Customer')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('customer.full_name')
                            ->label('Name')
                            ->weight('bold')
                            ->url(fn (RepairJob $record): ?string => CustomerResource::canEdit($record->customer) ? CustomerResource::getUrl('edit', ['record' => $record->customer]) : null),
                        TextEntry::make('customer.phone')
                            ->label('Mobile')
                            ->formatStateUsing(fn (?string $state): ?string => $state ? Phone::display($state) : null)
                            ->url(fn (RepairJob $record): ?string => $record->customer->phone ? Phone::telHref($record->customer->phone) : null)
                            ->placeholder('—'),
                        TextEntry::make('customer.email')
                            ->label('Email')
                            ->url(fn (RepairJob $record): ?string => $record->customer->email ? 'mailto:'.$record->customer->email : null)
                            ->placeholder('—'),
                        TextEntry::make('serviceArea.name')->label('Area')->placeholder('—'),
                        TextEntry::make('collection_address')
                            ->label('Collection address')
                            ->state(fn (RepairJob $record): ?string => $record->collectionAddress?->oneLine())
                            ->url(fn (RepairJob $record): ?string => $record->collectionAddress?->directionsUrl(), shouldOpenInNewTab: true)
                            ->placeholder('Not set'),
                        TextEntry::make('return_address')
                            ->label('Return address')
                            ->state(fn (RepairJob $record): string => $record->returnAddress?->oneLine() ?? 'Same as collection'),
                        TextEntry::make('collection')
                            ->label('Collection')
                            ->state(fn (RepairJob $record): ?string => $record->collectionSlotLabel())
                            ->placeholder('Not booked'),
                        TextEntry::make('lead.reference')
                            ->label('From lead')
                            ->url(fn (RepairJob $record): ?string => $record->lead && LeadResource::canView($record->lead) ? LeadResource::getUrl('view', ['record' => $record->lead]) : null)
                            ->placeholder('—'),
                    ]),

                Section::make('Status')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('reference')->copyable()->weight('bold'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('technician.name')->label('Technician')->placeholder('Unassigned'),
                        TextEntry::make('booked_at')->label('Booked')->dateTime('j M Y, H:i')->placeholder('—'),
                        TextEntry::make('collected_at')->label('Collected')->dateTime('j M Y, H:i')->placeholder('—'),
                        TextEntry::make('ready_at')->label('Ready')->dateTime('j M Y, H:i')->placeholder('—'),
                        TextEntry::make('returned_at')->label('Returned')->dateTime('j M Y, H:i')->placeholder('—'),
                        TextEntry::make('warranty_expires_at')->label('Warranty until')->date('j M Y')->placeholder('—'),
                    ]),

                Section::make('Device and fault')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('deviceModel.name')->label('Model')->placeholder('—'),
                        TextEntry::make('device_details')
                            ->label('Colour / storage')
                            ->state(fn (RepairJob $record): string => collect([$record->device_colour, $record->device_storage])->filter()->implode(' · ') ?: '—'),
                        TextEntry::make('imei')->label('IMEI')->copyable()->placeholder('Not recorded'),
                        TextEntry::make('serial_number')->label('Serial number')->copyable()->placeholder('Not recorded'),
                        TextEntry::make('reported_fault')->label('Reported fault')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('diagnosis')->columnSpanFull()->placeholder('Not diagnosed yet'),
                        TextEntry::make('customer_notes')->label('Customer’s notes')->columnSpanFull()->placeholder('—'),
                    ]),

                Section::make('Consents')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('terms_accepted_at')->label('Repair terms accepted')->dateTime('j M Y, H:i')->placeholder('Not recorded'),
                        TextEntry::make('start_within_cancellation_period')
                            ->label('Start within 14 days')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Customer asked us to start' : 'Check with the customer before starting')
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                    ]),

                Section::make('Repairs and price')
                    ->columnSpan(3)
                    ->visible(fn (): bool => RepairJobResource::canViewFinancials())
                    ->columns(4)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->columnSpanFull()
                            ->table([
                                RepeatableEntry\TableColumn::make('Repair'),
                                RepeatableEntry\TableColumn::make('Warranty'),
                                RepeatableEntry\TableColumn::make('Price')->alignEnd(),
                            ])
                            ->schema([
                                TextEntry::make('description'),
                                TextEntry::make('warranty_days')->formatStateUsing(fn (int $state): string => $state > 0 ? $state.' days' : 'None'),
                                TextEntry::make('price_pence')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                            ]),
                        TextEntry::make('quoted_price_pence')->label('Repairs')->formatStateUsing(fn (int $state): string => Money::format($state)),
                        TextEntry::make('collection_fee_pence')->label('Collection & return')->formatStateUsing(fn (int $state): string => $state > 0 ? Money::format($state) : 'Free'),
                        TextEntry::make('estimated_total')->label('Estimated total')->state(fn (RepairJob $record): string => $record->estimatedTotalLabel())->weight('bold'),
                        TextEntry::make('final_price_pence')
                            ->label('Final price')
                            ->formatStateUsing(fn (?int $state): ?string => $state !== null ? Money::format($state) : null)
                            ->placeholder('Not set')
                            ->weight('bold'),
                    ]),
            ]);
    }
}
