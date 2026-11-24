<?php

namespace App\Filament\Resources\Authors;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Authors\Pages\ManageAuthors;
use App\Filament\Resources\CatalogueFields;
use App\Models\Author;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Blog authors with public bio pages (expertise signals for search engines).
 */
class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static ?string $navigationLabel = 'Authors';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static ?int $navigationSort = 32;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            CatalogueFields::name(),
            CatalogueFields::slug(Author::class, 'The bio page address: /blog/author/{slug}.'),
            TextInput::make('role_title')
                ->label('Role')
                ->placeholder('e.g. Director and lead technician')
                ->maxLength(120),
            Select::make('user_id')
                ->label('Staff account')
                ->helperText('Posts you write default to this author.')
                ->relationship('user', 'name')
                ->searchable()
                ->preload(),
            Textarea::make('bio')
                ->helperText('A short introduction shown under each post.')
                ->rows(3)
                ->maxLength(1000)
                ->columnSpanFull(),
            Textarea::make('credentials')
                ->label('Experience and qualifications')
                ->helperText('Shown on the author page. Leave a blank line between paragraphs.')
                ->rows(4)
                ->maxLength(3000)
                ->columnSpanFull(),
            FileUpload::make('photo')
                ->image()
                ->avatar()
                ->disk('public')
                ->directory('authors')
                ->visibility('public')
                ->maxSize(2048),
            Repeater::make('social_links')
                ->label('Profile links')
                ->simple(TextInput::make('url')->url()->required()->maxLength(300))
                ->helperText('e.g. LinkedIn. Used to confirm the author’s identity to search engines.')
                ->addActionLabel('Add a link'),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->disk('public')
                    ->circular()
                    ->imageSize(36),
                TextColumn::make('name')
                    ->description(fn (Author $record): ?string => $record->role_title)
                    ->searchable(),
                TextColumn::make('posts_count')
                    ->label('Posts')
                    ->counts('posts')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuthors::route('/'),
        ];
    }
}
