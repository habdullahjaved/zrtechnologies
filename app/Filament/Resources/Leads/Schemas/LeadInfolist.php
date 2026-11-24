<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\LeadType;
use App\Models\Lead;
use App\Support\Money;
use App\Support\Phone;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Tabs::make('Lead')
                    ->columnSpan(2)
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Enquiry')
                            ->icon(Heroicon::OutlinedInboxStack)
                            ->columns(2)
                            ->schema([
                                TextEntry::make('name')->placeholder('Not given'),
                                TextEntry::make('company_name')->label('Company')->placeholder('—'),
                                TextEntry::make('phone')
                                    ->formatStateUsing(fn (?string $state): ?string => $state ? Phone::display($state) : null)
                                    ->url(fn (Lead $record): ?string => $record->phone ? Phone::telHref($record->phone) : null)
                                    ->copyable()
                                    ->placeholder('Not given'),
                                TextEntry::make('email')
                                    ->url(fn (Lead $record): ?string => $record->email ? 'mailto:'.$record->email : null)
                                    ->copyable()
                                    ->placeholder('Not given'),
                                TextEntry::make('postcode')->placeholder('—'),
                                TextEntry::make('serviceArea.name')->label('Service area')->placeholder('—'),
                                TextEntry::make('deviceModel.name')->label('iPhone model')->placeholder('—'),
                                TextEntry::make('repairType.name')->label('Repair')->placeholder('—'),
                                TextEntry::make('message')
                                    ->label('Message')
                                    ->columnSpanFull()
                                    ->placeholder('No message')
                                    ->prose(),
                                TextEntry::make('details.callback_time')->label('Best time to call')->visible(fn (Lead $record): bool => $record->type === LeadType::Callback),
                                TextEntry::make('details.fleet_size')->label('Number of devices')->visible(fn (Lead $record): bool => $record->type === LeadType::Business),
                            ]),

                        Tab::make('Booking progress')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->visible(fn (Lead $record): bool => $record->type === LeadType::Booking)
                            ->columns(2)
                            ->schema([
                                TextEntry::make('is_partial')
                                    ->label('Booking')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Not finished' : 'Completed')
                                    ->color(fn (bool $state): string => $state ? 'warning' : 'success'),
                                TextEntry::make('details.step')->label('Last step reached')->placeholder('—'),
                                TextEntry::make('details.repairs')
                                    ->label('Repairs chosen')
                                    ->listWithLineBreaks()
                                    ->bulleted()
                                    ->placeholder('None yet'),
                                TextEntry::make('details.slot')
                                    ->label('Collection slot chosen')
                                    ->placeholder('None yet'),
                            ]),

                        Tab::make('Attribution')
                            ->icon(Heroicon::OutlinedChartBar)
                            ->columns(2)
                            ->schema([
                                TextEntry::make('channel')->label('Channel (last touch)')->badge(),
                                TextEntry::make('first_touch_channel')->label('First touch')->badge()->placeholder('—'),
                                TextEntry::make('utm_source')->label('UTM source')->placeholder('—'),
                                TextEntry::make('utm_medium')->label('UTM medium')->placeholder('—'),
                                TextEntry::make('utm_campaign')->label('UTM campaign')->placeholder('—'),
                                TextEntry::make('utm_term')->label('UTM term')->placeholder('—'),
                                TextEntry::make('utm_content')->label('UTM content')->placeholder('—'),
                                TextEntry::make('landing_page')->label('Landing page')->placeholder('—')->copyable(),
                                TextEntry::make('referrer')->placeholder('—'),
                                TextEntry::make('device_type')->label('Device')->formatStateUsing(fn (?string $state): ?string => $state ? ucfirst($state) : null)->placeholder('—'),
                                TextEntry::make('gclid')->label('Google click ID (gclid)')->placeholder('—')->copyable(),
                                TextEntry::make('gbraid')->label('gbraid')->placeholder('—'),
                                TextEntry::make('wbraid')->label('wbraid')->placeholder('—'),
                                TextEntry::make('fbclid')->label('Meta click ID (fbclid)')->placeholder('—')->copyable(),
                                TextEntry::make('fbc')->label('Meta fbc')->placeholder('—'),
                                TextEntry::make('fbp')->label('Meta fbp')->placeholder('—'),
                                TextEntry::make('msclkid')->label('Microsoft click ID')->placeholder('—'),
                                TextEntry::make('ttclid')->label('TikTok click ID')->placeholder('—'),
                                TextEntry::make('ga_client_id')->label('GA4 client ID')->placeholder('—'),
                                TextEntry::make('event_id')->label('Conversion event ID')->copyable(),
                                KeyValueEntry::make('first_touch')
                                    ->label('First touch')
                                    ->state(fn (Lead $record): array => array_filter((array) $record->attributionValue('first_touch')))
                                    ->columnSpanFull(),
                                TextEntry::make('pages_viewed')
                                    ->label('Pages viewed before getting in touch')
                                    ->state(fn (Lead $record): array => collect((array) $record->attributionValue('pages'))
                                        ->map(fn (array $page): string => ($page['path'] ?? '').' · '.(isset($page['at']) ? now()->parse($page['at'])->format('H:i') : ''))
                                        ->all())
                                    ->listWithLineBreaks()
                                    ->placeholder('Not recorded')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Pipeline')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('reference')->copyable()->weight('bold'),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('lost_reason')->label('Lost because')->visible(fn (Lead $record): bool => filled($record->lost_reason)),
                        TextEntry::make('assignee.name')->label('Assigned to')->placeholder('Nobody yet'),
                        TextEntry::make('follow_up_at')
                            ->label('Follow up')
                            ->dateTime('j M Y, H:i')
                            ->color(fn (Lead $record): ?string => $record->follow_up_at?->isPast() && $record->status->isOpen() ? 'danger' : null)
                            ->placeholder('Not set'),
                        TextEntry::make('estimated_value_pence')
                            ->label('Estimated value')
                            ->formatStateUsing(fn (?int $state): ?string => $state !== null ? Money::format($state) : null)
                            ->placeholder('—'),
                        TextEntry::make('customer.full_name')
                            ->label('Customer record')
                            ->placeholder('Not linked yet'),
                        TextEntry::make('marketing_consent')
                            ->label('Marketing emails')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Opted in' : 'Not opted in')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('created_at')->label('Received')->dateTime('j M Y, H:i'),
                        TextEntry::make('contacted_at')->label('First contacted')->dateTime('j M Y, H:i')->placeholder('Not yet'),
                    ]),
            ]);
    }
}
