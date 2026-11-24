<?php

namespace App\Filament\Resources\Videos;

use App\Enums\VideoSource;
use App\Filament\NavigationGroup;
use App\Filament\Resources\CatalogueFields;
use App\Filament\Resources\ContentFields;
use App\Filament\Resources\Videos\Pages\CreateVideo;
use App\Filament\Resources\Videos\Pages\EditVideo;
use App\Filament\Resources\Videos\Pages\ListVideos;
use App\Models\Video;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Short videos. Keep a video as a placeholder until the real one is ready:
 * the site shows a branded "Video coming soon" card instead.
 */
class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static ?string $navigationLabel = 'Videos';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        $isSource = fn (Get $get, VideoSource $source): bool => $get('source') === $source || $get('source') === $source->value;

        return $schema->columns(3)->components([
            Section::make('Video')
                ->columns(2)
                ->columnSpan(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(160)
                        ->columnSpanFull(),
                    CatalogueFields::slug(Video::class, 'The video page address: /videos/{slug}.'),
                    Select::make('source')
                        ->options(VideoSource::class)
                        ->default(VideoSource::YouTube)
                        ->required()
                        ->native(false)
                        ->live(),
                    TextInput::make('external_url')
                        ->label('Video link')
                        ->helperText('The YouTube Shorts, TikTok or Instagram link.')
                        ->url()
                        ->maxLength(500)
                        ->hidden(fn (Get $get): bool => $isSource($get, VideoSource::Upload))
                        ->required(fn (Get $get): bool => ! $get('is_placeholder') && ! $isSource($get, VideoSource::Upload))
                        ->columnSpanFull(),
                    FileUpload::make('file_path')
                        ->label('Video file')
                        ->helperText('MP4 or WebM, up to 50 MB. Vertical (9:16) works best.')
                        ->disk('public')
                        ->directory('videos')
                        ->visibility('public')
                        ->acceptedFileTypes(['video/mp4', 'video/webm'])
                        ->maxSize(51200)
                        ->visible(fn (Get $get): bool => $isSource($get, VideoSource::Upload))
                        ->required(fn (Get $get): bool => ! $get('is_placeholder') && $isSource($get, VideoSource::Upload))
                        ->columnSpanFull(),
                    FileUpload::make('poster_path')
                        ->label('Cover image')
                        ->helperText('Shown before the video plays. YouTube videos use their own thumbnail if you leave this empty.')
                        ->image()
                        ->disk('public')
                        ->directory('videos/posters')
                        ->visibility('public')
                        ->maxSize(2048),
                    TextInput::make('duration_seconds')
                        ->label('Length')
                        ->suffix('seconds')
                        ->integer()
                        ->minValue(1)
                        ->maxValue(3600),
                    Textarea::make('description')
                        ->rows(2)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    Textarea::make('transcript')
                        ->helperText('What is said in the video. Shown on the video page and read by search engines.')
                        ->rows(4)
                        ->maxLength(10000)
                        ->columnSpanFull(),
                ]),
            Section::make('Display')
                ->columnSpan(1)
                ->schema([
                    Toggle::make('is_placeholder')
                        ->label('Placeholder (video coming soon)')
                        ->helperText('Switch off once the real video is added.')
                        ->default(true)
                        ->live(),
                    TextInput::make('placeholder_label')
                        ->label('Placeholder label')
                        ->placeholder('e.g. Screen replacement in 60 seconds')
                        ->maxLength(120)
                        ->visible(fn (Get $get): bool => (bool) $get('is_placeholder')),
                    Select::make('service_id')
                        ->label('Repair page')
                        ->helperText('Shown on this repair page and linked from the video.')
                        ->relationship('service', 'name')
                        ->preload(),
                    Toggle::make('show_on_home')
                        ->label('Show on the home page')
                        ->default(true),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    DateTimePicker::make('published_at')
                        ->label('Published')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i'),
                    TextInput::make('sort')
                        ->label('Sort order')
                        ->integer()
                        ->minValue(0)
                        ->default(fn (): int => (int) Video::query()->max('sort') + 10)
                        ->required(),
                ]),
            ContentFields::seo(
                defaultTitle: fn (Get $get): string => (string) ($get('../title') ?: 'New video'),
                defaultDescription: fn (Get $get): ?string => $get('../description'),
                url: fn (?Model $record): ?string => $record instanceof Video ? $record->url() : null,
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('title')
                    ->description(fn (Video $record): ?string => $record->service?->name)
                    ->searchable(),
                TextColumn::make('source')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_placeholder')
                    ->label('Placeholder')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('success'),
                IconColumn::make('show_on_home')
                    ->label('Home page')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('service'))
            ->filters([
                TernaryFilter::make('is_placeholder')->label('Placeholder'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVideos::route('/'),
            'create' => CreateVideo::route('/create'),
            'edit' => EditVideo::route('/{record}/edit'),
        ];
    }
}
