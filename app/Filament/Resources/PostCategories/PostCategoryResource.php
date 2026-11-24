<?php

namespace App\Filament\Resources\PostCategories;

use App\Filament\NavigationGroup;
use App\Filament\Resources\CatalogueFields;
use App\Filament\Resources\PostCategories\Pages\ManagePostCategories;
use App\Models\PostCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PostCategoryResource extends Resource
{
    protected static ?string $model = PostCategory::class;

    protected static ?string $modelLabel = 'blog category';

    protected static ?string $navigationLabel = 'Blog categories';

    protected static ?string $slug = 'blog-categories';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            CatalogueFields::name(),
            CatalogueFields::slug(PostCategory::class, 'The category address: /blog/{slug}.'),
            Textarea::make('description')
                ->helperText('Shown at the top of the category page and as its meta description.')
                ->rows(3)
                ->maxLength(500)
                ->columnSpanFull(),
            TextInput::make('sort')
                ->label('Sort order')
                ->integer()
                ->minValue(0)
                ->default(fn (): int => (int) PostCategory::query()->max('sort') + 10)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')
                    ->description(fn (PostCategory $record): string => '/blog/'.$record->slug)
                    ->searchable(),
                TextColumn::make('posts_count')
                    ->label('Posts')
                    ->counts('posts')
                    ->badge()
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->iconButton()
                    ->disabled(fn (PostCategory $record): bool => $record->posts()->exists())
                    ->tooltip(fn (PostCategory $record): ?string => $record->posts()->exists() ? 'Move its posts to another category first.' : null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePostCategories::route('/'),
        ];
    }
}
