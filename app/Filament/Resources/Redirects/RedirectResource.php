<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Redirect;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

/**
 * 301/302 redirects. Pages that change address get one automatically.
 */
class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $navigationLabel = 'Redirects';

    protected static ?string $recordTitleAttribute = 'from_path';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Seo;

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::fields());
    }

    /**
     * Shared with the 404 log's "Create redirect" action.
     *
     * @return list<Component|Field>
     */
    public static function fields(bool $withFrom = true): array
    {
        return array_values(array_filter([
            $withFrom ? TextInput::make('from_path')
                ->label('Old address')
                ->helperText('The path that should redirect, e.g. /old-page.')
                ->prefix(url('/'))
                ->required()
                ->maxLength(500)
                ->dehydrateStateUsing(fn (string $state): string => Redirect::normalisePath($state))
                ->rule(fn (?Redirect $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                    $exists = Redirect::query()
                        ->where('from_path', Redirect::normalisePath((string) $value))
                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                        ->exists();

                    if ($exists) {
                        $fail('There is already a redirect from this address.');
                    }
                }) : null,
            TextInput::make('to_path')
                ->label('New address')
                ->helperText('A path on this site (e.g. /iphone-repair/screen-replacement) or a full web address.')
                ->required()
                ->maxLength(500)
                ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $withFrom): void {
                    if ($withFrom && Redirect::normalisePath((string) $value) === Redirect::normalisePath((string) $get('from_path'))) {
                        $fail('A page cannot redirect to itself.');
                    }
                }),
            Select::make('status_code')
                ->label('Type')
                ->options([301 => 'Permanent (301)', 302 => 'Temporary (302)'])
                ->default(301)
                ->required()
                ->rule(Rule::in([301, 302])),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('from_path')
                    ->label('From')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('to_path')
                    ->label('To')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status_code')
                    ->label('Type')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('hits')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_hit_at')
                    ->label('Last used')
                    ->since()
                    ->placeholder('Never')
                    ->sortable(),
                IconColumn::make('is_automatic')
                    ->label('Automatic')
                    ->boolean()
                    ->tooltip('Created when a page’s address changed'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_automatic')->label('Automatic'),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRedirects::route('/'),
        ];
    }
}
