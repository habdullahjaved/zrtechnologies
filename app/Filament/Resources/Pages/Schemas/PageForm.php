<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Resources\CatalogueFields;
use App\Filament\Resources\ContentFields;
use App\Models\Page;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSystem = fn (?Page $record): bool => $record?->isSystem() ?? false;

        return $schema->columns(3)->components([
            Section::make('Page')
                ->columns(2)
                ->columnSpan(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Heading (H1)')
                        ->helperText('The main heading at the top of the page.')
                        ->required()
                        ->maxLength(160)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state, string $operation): void {
                            if ($operation === 'create' && ($get('slug') ?? '') === Str::slug((string) $old)) {
                                $set('slug', Str::slug((string) $state));
                            }
                        })
                        ->columnSpanFull(),
                    CatalogueFields::slug(Page::class, 'The page’s web address, e.g. "careers" for /careers.')
                        ->prefix(url('/').'/')
                        ->disabled($isSystem)
                        ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (in_array($value, self::reservedSlugs(), true)) {
                                $fail('This address is already used by another part of the website. Choose a different slug.');
                            }
                        })
                        ->helperText(fn (?Page $record): string => $record?->isSystem()
                            ? 'This page’s address is fixed.'
                            : 'The page’s web address. Changing it later redirects the old address automatically.'),
                    TextInput::make('eyebrow')
                        ->label('Small heading above the title')
                        ->maxLength(80),
                    Textarea::make('intro')
                        ->label('Introduction')
                        ->helperText('One or two sentences under the heading. Also used as the meta description if none is set below.')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ]),
            Section::make('Publishing')
                ->columnSpan(1)
                ->schema([
                    Select::make('status')
                        ->options(PublishStatus::class)
                        ->default(PublishStatus::Draft)
                        ->required()
                        ->native(false)
                        ->live(),
                    DateTimePicker::make('published_at')
                        ->label('Publish date')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->helperText('Scheduled pages go live at this time.')
                        ->required(fn (Get $get): bool => $get('status') === PublishStatus::Scheduled || $get('status') === PublishStatus::Scheduled->value),
                    Toggle::make('show_in_sitemap')
                        ->label('Include in the sitemap')
                        ->default(true),
                    Toggle::make('needs_legal_review')
                        ->label('Needs legal review')
                        ->helperText('Draft legal wording that the owner or a legal adviser should check before launch.'),
                ]),
            Section::make('Content')
                ->description(fn (?Page $record): ?string => $record?->isSystem() && ! $record->key?->isContentPage()
                    ? 'This page has its own layout. Anything added here appears as extra content on the page.'
                    : null)
                ->columnSpanFull()
                ->schema([
                    ContentFields::blocks(),
                    ContentFields::faqs(),
                ]),
            ContentFields::seo(
                defaultTitle: fn (Get $get, ?Model $record): string => (string) ($get('../title') ?: ($record instanceof Page ? $record->title : 'New page')),
                defaultDescription: fn (Get $get): ?string => filled($get('../intro')) ? Str::limit((string) $get('../intro'), 160) : null,
                url: fn (?Model $record): ?string => $record instanceof Page ? $record->url() : null,
                contentText: fn (Get $get): string => $get('../intro').' '.ContentFields::blocksText($get('../blocks')),
            ),
        ]);
    }

    /**
     * First path segments already taken by routes (contact, blog, admin...).
     *
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        $segments = [trim((string) config('zr.admin.path'), '/'), 'livewire', 'storage', 'build'];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->isFallback) {
                continue;
            }

            $first = explode('/', trim($route->uri(), '/'))[0];

            if ($first !== '' && ! str_contains($first, '{')) {
                $segments[] = $first;
            }
        }

        return array_values(array_unique($segments));
    }
}
