<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Resources\CatalogueFields;
use App\Filament\Resources\ContentFields;
use App\Models\Author;
use App\Models\Post;
use App\Services\Content\InternalLinkSuggester;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Post')
                ->columns(2)
                ->columnSpan(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(160)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state, string $operation): void {
                            if ($operation === 'create' && ($get('slug') ?? '') === Str::slug((string) $old)) {
                                $set('slug', Str::slug((string) $state));
                            }
                        })
                        ->columnSpanFull(),
                    CatalogueFields::slug(Post::class, 'The post’s address: /blog/{slug}. Changing it later redirects the old address automatically.')
                        ->prefix('/blog/')
                        ->columnSpanFull(),
                    Textarea::make('excerpt')
                        ->helperText('A short summary for post cards, RSS and the meta description. Up to 400 characters.')
                        ->rows(3)
                        ->maxLength(400)
                        ->columnSpanFull(),
                    FileUpload::make('featured_image')
                        ->label('Featured image')
                        ->helperText('1200×630 works best. Shown at the top of the post and when it is shared.')
                        ->image()
                        ->disk('public')
                        ->directory('posts')
                        ->visibility('public')
                        ->maxSize(4096)
                        ->live(),
                    TextInput::make('featured_image_alt')
                        ->label('Featured image alt text')
                        ->helperText('Describe the image for people who cannot see it.')
                        ->maxLength(200)
                        ->required(fn (Get $get): bool => filled($get('featured_image'))),
                ]),
            Section::make('Publishing')
                ->columnSpan(1)
                ->schema([
                    Select::make('status')
                        ->options(PublishStatus::class)
                        ->default(PublishStatus::Draft)
                        ->required()
                        ->native(false)
                        ->live()
                        ->helperText('Draft → Ready for review → Published. Scheduled posts go live on their publish date.'),
                    DateTimePicker::make('published_at')
                        ->label('Publish date')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->required(fn (Get $get): bool => self::statusIs($get, PublishStatus::Scheduled))
                        ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if (self::statusIs($get, PublishStatus::Scheduled) && filled($value) && now()->gte($value)) {
                                $fail('A scheduled post needs a publish date in the future.');
                            }
                        }),
                    Select::make('post_category_id')
                        ->label('Category')
                        ->relationship('category', 'name', fn (Builder $query) => $query->orderBy('sort'))
                        ->required()
                        ->preload(),
                    Select::make('author_id')
                        ->label('Author')
                        ->relationship('author', 'name', fn (Builder $query) => $query->where('is_active', true))
                        ->default(fn (): ?int => Author::query()->where('user_id', auth()->id())->value('id') ?? Author::query()->value('id'))
                        ->preload(),
                    Select::make('tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')->required()->maxLength(80),
                        ]),
                    Select::make('service_id')
                        ->label('Linked repair page')
                        ->helperText('Adds a "See prices and book" box for this repair at the end of the post.')
                        ->relationship('service', 'name')
                        ->preload(),
                    Toggle::make('is_ai_assisted')
                        ->label('Drafted with AI help')
                        ->helperText('For your records. A person must check and approve every post before it is published.'),
                ]),
            Section::make('Content')
                ->columnSpanFull()
                ->schema([
                    ContentFields::blocks(),
                    TextEntry::make('link_suggestions')
                        ->label('Internal link suggestions')
                        ->state(fn (Get $get): HtmlString => self::suggestions($get('blocks')))
                        ->html(),
                    ContentFields::faqs(),
                    Select::make('relatedPosts')
                        ->label('Related posts')
                        ->helperText('Shown at the end of the post. If you choose none, the latest posts in the same category are shown.')
                        ->relationship('relatedPosts', 'title', fn (Builder $query, ?Post $record) => $query->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey())))
                        ->multiple()
                        ->searchable()
                        ->maxItems(3),
                ]),
            ContentFields::seo(
                defaultTitle: fn (Get $get): string => (string) ($get('../title') ?: 'New post'),
                defaultDescription: fn (Get $get): ?string => $get('../excerpt'),
                url: fn (?Model $record): ?string => $record instanceof Post ? $record->url() : null,
                contentText: fn (Get $get): string => ContentFields::blocksText($get('../blocks')),
            ),
        ]);
    }

    private static function statusIs(Get $get, PublishStatus $status): bool
    {
        $state = $get('status');

        return $state === $status || $state === $status->value;
    }

    /**
     * @param  array<int|string, mixed>|null  $blocks
     */
    private static function suggestions(?array $blocks): HtmlString
    {
        $suggestions = app(InternalLinkSuggester::class)->suggest($blocks);

        if ($suggestions === []) {
            return new HtmlString('<span style="font-size:14px">No suggestions yet. Mention a repair or an iPhone model and a link will be suggested.</span>');
        }

        $items = array_map(
            fn (array $suggestion): string => '<li>Link “'.e($suggestion['phrase']).'” to <a href="'.e($suggestion['url']).'" target="_blank" style="text-decoration:underline">'.e($suggestion['label']).'</a></li>',
            $suggestions,
        );

        return new HtmlString('<ul style="display:grid;gap:4px;font-size:14px">'.implode('', $items).'</ul>');
    }
}
