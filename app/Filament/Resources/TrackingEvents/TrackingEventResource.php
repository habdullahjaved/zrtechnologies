<?php

namespace App\Filament\Resources\TrackingEvents;

use App\Enums\TrackingPlatform;
use App\Enums\TrackingStatus;
use App\Filament\NavigationGroup;
use App\Filament\Resources\TrackingEvents\Pages\ListTrackingEvents;
use App\Jobs\SendGa4Event;
use App\Jobs\SendMetaConversion;
use App\Models\TrackingEvent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Every server-side conversion: what was sent to Meta, GA4 and the Google Ads
 * export, and what came back. Personal data is shown hashed only.
 */
class TrackingEventResource extends Resource
{
    protected static ?string $model = TrackingEvent::class;

    protected static ?string $modelLabel = 'tracking event';

    protected static ?string $navigationLabel = 'Tracking events';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Marketing;

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('event_name'),
            TextEntry::make('platform')->badge(),
            TextEntry::make('status')->badge(),
            TextEntry::make('event_id')->copyable(),
            TextEntry::make('error')->placeholder('None')->columnSpanFull(),
            TextEntry::make('payload')
                ->label('Payload (personal data hashed)')
                ->state(fn (TrackingEvent $record): string => (string) json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                ->fontFamily('mono')
                ->columnSpanFull(),
            TextEntry::make('response_body')->label('Response')->placeholder('None')->fontFamily('mono')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['lead', 'repairJob']))
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('platform')->badge()->color('gray'),
                TextColumn::make('event_name')->label('Event'),
                TextColumn::make('status')->badge(),
                TextColumn::make('lead.reference')->label('Lead')->placeholder('—'),
                TextColumn::make('repairJob.reference')->label('Job')->placeholder('—'),
                TextColumn::make('response_code')->label('HTTP')->placeholder('—')->toggleable(),
                TextColumn::make('error')->limit(60)->placeholder('—')->wrap()->toggleable(),
                IconColumn::make('is_test')->label('Test')->boolean()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('platform')->options(TrackingPlatform::class),
                SelectFilter::make('status')->options(TrackingStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->label('Send again')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (TrackingEvent $record): bool => $record->status === TrackingStatus::Failed
                        && in_array($record->platform, [TrackingPlatform::Meta, TrackingPlatform::Ga4], true)
                        && auth()->user()?->can('Update:TrackingEvent'))
                    ->requiresConfirmation()
                    ->action(function (TrackingEvent $record): void {
                        $record->update(['status' => TrackingStatus::Queued, 'error' => null]);

                        $record->platform === TrackingPlatform::Meta
                            ? SendMetaConversion::dispatch($record)
                            : SendGa4Event::dispatch($record);

                        Notification::make()->title('Queued to send again')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrackingEvents::route('/'),
        ];
    }
}
