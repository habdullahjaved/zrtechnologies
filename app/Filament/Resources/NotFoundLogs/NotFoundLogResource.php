<?php

namespace App\Filament\Resources\NotFoundLogs;

use App\Filament\NavigationGroup;
use App\Filament\Resources\NotFoundLogs\Pages\ListNotFoundLogs;
use App\Filament\Resources\Redirects\RedirectResource;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Addresses visitors asked for that do not exist, most requested first, with
 * a one-click "Create redirect".
 */
class NotFoundLogResource extends Resource
{
    protected static ?string $model = NotFoundLog::class;

    protected static ?string $modelLabel = 'missing page';

    protected static ?string $navigationLabel = '404 log';

    protected static ?string $slug = 'not-found-log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Seo;

    protected static ?int $navigationSort = 20;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('path')
                    ->searchable()
                    ->copyable()
                    ->wrap(),
                TextColumn::make('hits')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_seen_at')
                    ->label('Last seen')
                    ->since()
                    ->sortable(),
                TextColumn::make('last_referrer')
                    ->label('Came from')
                    ->limit(50)
                    ->placeholder('Direct or unknown')
                    ->toggleable(),
                TextColumn::make('redirect.to_path')
                    ->label('Redirected to')
                    ->placeholder('Not redirected')
                    ->badge()
                    ->color('success'),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('redirect'))
            ->filters([
                TernaryFilter::make('redirect_id')
                    ->label('Redirected')
                    ->nullable()
                    ->default(false),
            ])
            ->recordActions([
                Action::make('redirect')
                    ->label('Create redirect')
                    ->icon(Heroicon::OutlinedArrowUturnRight)
                    ->visible(fn (NotFoundLog $record): bool => $record->redirect_id === null && RedirectResource::canCreate())
                    ->schema(RedirectResource::fields(withFrom: false))
                    ->action(function (NotFoundLog $record, array $data): void {
                        $redirect = Redirect::query()->updateOrCreate(
                            ['from_path' => Redirect::normalisePath($record->path)],
                            [...$data, 'is_automatic' => false],
                        );

                        $record->update(['redirect_id' => $redirect->getKey()]);

                        Notification::make()->title('Redirect created')->success()->send();
                    }),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotFoundLogs::route('/'),
        ];
    }
}
