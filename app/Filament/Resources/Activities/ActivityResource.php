<?php

namespace App\Filament\Resources\Activities;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Activities\Pages\ListActivities;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Read-only audit trail of changes made across the admin.
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'audit log';

    protected static ?string $navigationLabel = 'Audit log';

    protected static ?string $slug = 'audit-log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['causer', 'subject']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('description'),
                    TextEntry::make('created_at')->label('When')->dateTime('d/m/Y H:i:s'),
                    TextEntry::make('causer.name')->label('By')->placeholder('System'),
                    TextEntry::make('subject_type')
                        ->label('Record')
                        ->formatStateUsing(fn (Activity $record): string => self::subjectLabel($record)),
                ]),
            Section::make('Changes')
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn (Activity $record): bool => filled($record->attribute_changes))
                ->schema([
                    KeyValueEntry::make('attribute_changes.old')
                        ->label('Before')
                        ->keyLabel('Field')
                        ->valueLabel('Value'),
                    KeyValueEntry::make('attribute_changes.attributes')
                        ->label('After')
                        ->keyLabel('Field')
                        ->valueLabel('Value'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('By')
                    ->placeholder('System'),
                TextColumn::make('description')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('subject_type')
                    ->label('Record')
                    ->formatStateUsing(fn (Activity $record): string => self::subjectLabel($record)),
                TextColumn::make('log_name')
                    ->label('Area')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state)->headline()->toString()),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Area')
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('log_name')
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->map(fn (string $name): string => str($name)->headline()->toString())
                        ->all()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('From')->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('Until')->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }

    private static function subjectLabel(Activity $record): string
    {
        if (! $record->subject_type) {
            return '—';
        }

        $type = str(class_basename($record->subject_type))->headline()->toString();
        $name = $record->subject?->getAttribute('name');

        return $name ? "{$type}: {$name}" : "{$type} #{$record->subject_id}";
    }
}
