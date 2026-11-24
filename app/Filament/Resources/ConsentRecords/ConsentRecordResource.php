<?php

namespace App\Filament\Resources\ConsentRecords;

use App\Filament\NavigationGroup;
use App\Filament\Resources\ConsentRecords\Pages\ListConsentRecords;
use App\Models\ConsentRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Proof of every cookie choice (UK GDPR accountability). Read-only.
 */
class ConsentRecordResource extends Resource
{
    protected static ?string $model = ConsentRecord::class;

    protected static ?string $modelLabel = 'cookie choice';

    protected static ?string $navigationLabel = 'Consent log';

    protected static ?string $slug = 'consent-log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Marketing;

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ConsentRecord::ACCEPT_ALL => 'Accepted all',
                        ConsentRecord::REJECT_ALL => 'Rejected all',
                        default => 'Chose settings',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        ConsentRecord::ACCEPT_ALL => 'success',
                        ConsentRecord::REJECT_ALL => 'gray',
                        default => 'info',
                    }),
                IconColumn::make('analytics')->boolean(),
                IconColumn::make('marketing')->boolean(),
                TextColumn::make('policy_version')->label('Policy'),
                TextColumn::make('page')->placeholder('—')->toggleable(),
                TextColumn::make('consent_id')->label('Consent ID')->copyable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')->options([
                    ConsentRecord::ACCEPT_ALL => 'Accepted all',
                    ConsentRecord::REJECT_ALL => 'Rejected all',
                    ConsentRecord::CUSTOM => 'Chose settings',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsentRecords::route('/'),
        ];
    }
}
