<?php

namespace App\Filament\RelationManagers;

use App\Models\Note;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Internal notes, shared by leads, customers and repair jobs.
 * Anyone who can view the record can add notes; only the author edits or deletes them.
 */
class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notes';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedChatBubbleBottomCenterText;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = Note::query()->whereMorphedTo('notable', $ownerRecord)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')
                ->label('Note')
                ->required()
                ->rows(4)
                ->maxLength(5000)
                ->columnSpanFull(),
            Toggle::make('is_pinned')
                ->label('Pin to top'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn ($query) => $query->orderByDesc('is_pinned')->orderByDesc('created_at'))
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                IconColumn::make('is_pinned')
                    ->label('')
                    ->icon(fn (bool $state): ?Heroicon => $state ? Heroicon::Bookmark : null)
                    ->color('warning')
                    ->width('1%'),
                TextColumn::make('body')
                    ->label('Note')
                    ->wrap()
                    ->formatStateUsing(fn (string $state): string => nl2br(e($state)))
                    ->html(),
                TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('System'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->dateTimeTooltip('j M Y, H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add note')
                    ->modalHeading('Add a note')
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'user_id' => auth()->id()]),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (Note $record): bool => $record->user_id === auth()->id()),
                DeleteAction::make()->visible(fn (Note $record): bool => $record->user_id === auth()->id()),
            ])
            ->emptyStateHeading('No notes yet')
            ->emptyStateDescription('Add call notes, what the customer said or anything the team should know.')
            ->paginated([10, 25]);
    }
}
