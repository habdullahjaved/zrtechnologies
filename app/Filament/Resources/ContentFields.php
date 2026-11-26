<?php

namespace App\Filament\Resources;

use App\Models\DeviceModel;
use App\Models\Faq;
use App\Models\RepairType;
use App\Models\Video;
use App\Settings\SiteSettings;
use App\Support\Content\Blocks;
use Closure;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Form fields shared by the content and SEO screens: the block builder, the
 * SEO section (with search-result preview and checklist) and FAQ pickers.
 */
final class ContentFields
{
    /** Google truncates titles at roughly this width on desktop. */
    private const int TITLE_PIXEL_LIMIT = 580;

    /**
     * The page builder. Block types are rendered by resources/views/components/blocks.
     */
    public static function blocks(string $field = 'blocks'): Builder
    {
        return Builder::make($field)
            ->label('Page content')
            ->helperText('Add sections in the order they should appear. Use headings (H2) for the main sections: they build the table of contents.')
            ->blockNumbers(false)
            ->collapsible()
            ->cloneable()
            ->reorderableWithButtons()
            ->addActionLabel('Add a section')
            ->columnSpanFull()
            ->blocks([
                Block::make('heading')
                    ->label(fn (?array $state): string => filled($state['text'] ?? null) ? 'Heading: '.$state['text'] : 'Heading')
                    ->icon(Heroicon::OutlinedHashtag)
                    ->columns(4)
                    ->schema([
                        TextInput::make('text')->required()->maxLength(120)->columnSpan(3),
                        Select::make('level')->options(['h2' => 'Main heading (H2)', 'h3' => 'Sub-heading (H3)'])->default('h2')->required()->native(false),
                    ]),
                Block::make('rich_text')
                    ->label('Text')
                    ->icon(Heroicon::OutlinedBars3BottomLeft)
                    ->schema([
                        RichEditor::make('content')
                            ->hiddenLabel()
                            ->required()
                            ->toolbarButtons([
                                ['bold', 'italic', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList', 'blockquote'],
                                ['table', 'attachFiles'],
                                ['undo', 'redo'],
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('content')
                            ->fileAttachmentsVisibility('public'),
                    ]),
                Block::make('image')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columns(2)
                    ->schema([
                        FileUpload::make('path')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('content')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('alt')
                            ->label('Alt text')
                            ->helperText('Describe the image for people who cannot see it.')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('caption')->maxLength(200),
                    ]),
                Block::make('callout')
                    ->label('Tip or warning box')
                    ->icon(Heroicon::OutlinedLightBulb)
                    ->columns(3)
                    ->schema([
                        Select::make('tone')->options(['tip' => 'Tip', 'info' => 'Good to know', 'warning' => 'Warning'])->default('tip')->required()->native(false),
                        TextInput::make('title')->maxLength(120)->columnSpan(2),
                        Textarea::make('text')->required()->rows(3)->maxLength(1000)->columnSpanFull(),
                    ]),
                Block::make('table')
                    ->label('Comparison table')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->schema([
                        TextInput::make('caption')->maxLength(150),
                        TextInput::make('header')
                            ->label('Column headings')
                            ->helperText('Separate columns with a | e.g. "Feature | Tempered glass | Film".')
                            ->maxLength(300),
                        Repeater::make('rows')
                            ->simple(TextInput::make('cells')->placeholder('Scratch protection | Excellent | Good')->required()->maxLength(500))
                            ->minItems(1)
                            ->addActionLabel('Add a row'),
                    ]),
                Block::make('steps')
                    ->label('Numbered steps')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('title')->required()->maxLength(120),
                                Textarea::make('text')->rows(2)->maxLength(600),
                            ])
                            ->minItems(1)
                            ->addActionLabel('Add a step'),
                    ]),
                Block::make('faqs')
                    ->label('FAQs')
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->schema([
                        TextInput::make('title')->placeholder('Frequently asked questions')->maxLength(120),
                        Select::make('faq_ids')
                            ->label('Questions')
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => Faq::query()->listed()->pluck('question', 'id')->all())
                            ->required(),
                    ]),
                Block::make('video')
                    ->icon(Heroicon::OutlinedPlayCircle)
                    ->schema([
                        Select::make('video_id')
                            ->label('Video')
                            ->options(fn (): array => Video::query()->listed()->pluck('title', 'id')->all())
                            ->searchable()
                            ->required(),
                    ]),
                Block::make('cta')
                    ->label('Repair quote button')
                    ->icon(Heroicon::OutlinedCursorArrowRays)
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')->placeholder('Need it fixed? We collect, repair and return.')->maxLength(120)->columnSpanFull(),
                        Textarea::make('text')->rows(2)->maxLength(300)->columnSpanFull(),
                        TextInput::make('button_label')->placeholder('Request a quote')->maxLength(40),
                        Select::make('repair_type_id')
                            ->label('Prefill repair')
                            ->options(fn (): array => RepairType::query()->listed()->pluck('name', 'id')->all())
                            ->searchable(),
                        Select::make('device_model_id')
                            ->label('Prefill model')
                            ->options(fn (): array => DeviceModel::query()->listed()->pluck('name', 'id')->all())
                            ->searchable(),
                    ]),
                Block::make('price_table')
                    ->label('Price table')
                    ->icon(Heroicon::OutlinedCurrencyPound)
                    ->columns(2)
                    ->schema([
                        Select::make('repair_type_id')
                            ->label('Repair')
                            ->helperText('Shows the live price of this repair for every model.')
                            ->options(fn (): array => RepairType::query()->listed()->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('title')->placeholder('Screen Replacement prices')->maxLength(120),
                    ]),
            ]);
    }

    /**
     * SEO overrides saved to the record's seoMeta relationship, with a live
     * search-result preview and checklist.
     *
     * @param  Closure(Get, ?Model): string  $defaultTitle  The generated title when no override is set (without the site name).
     * @param  Closure(Get, ?Model): ?string  $defaultDescription
     * @param  Closure(?Model): ?string  $url
     * @param  Closure(Get, ?Model): string|null  $contentText  The page's own text, for the keyword and length checks.
     */
    public static function seo(Closure $defaultTitle, Closure $defaultDescription, Closure $url, ?Closure $contentText = null): Section
    {
        return Section::make('Search engines and sharing')
            ->description('Leave a field empty to use the automatic title and description.')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->relationship('seoMeta')
            ->collapsible()
            ->columnSpanFull()
            ->schema([
                TextEntry::make('serp_preview')
                    ->label('Google preview')
                    ->state(fn (Get $get, mixed $livewire): HtmlString => self::serpPreview(
                        self::title($get, self::owner($livewire), $defaultTitle),
                        filled($get('description')) ? (string) $get('description') : (string) ($defaultDescription($get, self::owner($livewire)) ?? app(SiteSettings::class)->default_meta_description),
                        $url(self::owner($livewire)),
                    ))
                    ->html()
                    ->columnSpanFull(),
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->label('SEO title')
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->placeholder(fn (Get $get, mixed $livewire): string => $defaultTitle($get, self::owner($livewire)))
                        ->helperText(fn (Get $get, mixed $livewire): string => self::titleHelper(self::title($get, self::owner($livewire), $defaultTitle))),
                    TextInput::make('focus_keyword')
                        ->label('Focus keyword')
                        ->helperText('The main search phrase this page should rank for, e.g. "iPhone screen repair Blackburn".')
                        ->maxLength(120)
                        ->live(onBlur: true),
                    Textarea::make('description')
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(320)
                        ->live(onBlur: true)
                        ->placeholder(fn (Get $get, mixed $livewire): ?string => $defaultDescription($get, self::owner($livewire)))
                        ->helperText(fn (?string $state): string => Str::length((string) $state).' characters. Aim for 140–160.')
                        ->columnSpanFull(),
                ]),
                TextEntry::make('seo_checklist')
                    ->label('SEO checklist')
                    ->state(fn (Get $get, mixed $livewire): HtmlString => self::checklist(
                        title: self::title($get, self::owner($livewire), $defaultTitle),
                        description: filled($get('description')) ? (string) $get('description') : (string) $defaultDescription($get, self::owner($livewire)),
                        keyword: (string) $get('focus_keyword'),
                        content: $contentText ? (string) $contentText($get, self::owner($livewire)) : null,
                        noindex: (bool) $get('noindex'),
                    ))
                    ->html()
                    ->columnSpanFull(),
                Section::make('Advanced')
                    ->collapsed()
                    ->compact()
                    ->columns(2)
                    ->schema([
                        Toggle::make('noindex')
                            ->label('Hide from search engines (noindex)')
                            ->live(),
                        Toggle::make('nofollow')
                            ->label('Ask search engines not to follow links (nofollow)'),
                        TextInput::make('canonical_url')
                            ->label('Canonical URL')
                            ->helperText('Only set this if another page is the main version of this content.')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),
                        FileUpload::make('og_image')
                            ->label('Share image')
                            ->helperText('Shown when the page is shared on social media or WhatsApp. 1200×630 pixels.')
                            ->image()
                            ->disk('public')
                            ->directory('seo')
                            ->visibility('public')
                            ->maxSize(2048),
                        TextInput::make('og_image_alt')
                            ->label('Share image description')
                            ->maxLength(200),
                        Textarea::make('schema_override')
                            ->label('Extra structured data (JSON-LD)')
                            ->helperText('Advanced: one schema.org object, or a list of them, added to the page’s structured data. Leave empty unless you know you need it.')
                            ->rows(6)
                            ->formatStateUsing(fn (mixed $state): ?string => is_array($state) && $state !== [] ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null)
                            ->dehydrateStateUsing(fn (?string $state): ?array => filled($state) ? json_decode($state, true) : null)
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (filled($value) && ! is_array(json_decode((string) $value, true))) {
                                    $fail('This must be valid JSON: an object like {"@type": "..."} or a list of objects.');
                                }
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * FAQs shown on the record's page (attached through the faqables table).
     */
    public static function faqs(): Select
    {
        return Select::make('faqs')
            ->label('FAQs shown on this page')
            ->helperText('These appear at the bottom of the page and in its FAQ structured data.')
            ->relationship('faqs', 'question', fn ($query) => $query->where('is_active', true))
            ->multiple()
            ->searchable()
            ->preload()
            ->columnSpanFull();
    }

    /**
     * The supplied share images in /public/images/og, for records that use them.
     *
     * @return array<string, string>
     */
    public static function suppliedShareImages(): array
    {
        $options = [];

        foreach (File::files(public_path('images/og')) as $file) {
            if ($file->getExtension() === 'jpg' && $file->getFilename() !== 'og-template-blank.jpg') {
                $options['images/og/'.$file->getFilename()] = Str::headline(Str::after($file->getFilenameWithoutExtension(), 'og-'));
            }
        }

        return $options;
    }

    /**
     * Plain text of a block-builder state, for checks while editing.
     *
     * @param  array<int|string, mixed>|null  $blocks
     */
    public static function blocksText(?array $blocks): string
    {
        return Blocks::plainText($blocks);
    }

    /**
     * Rough rendered width of a title in Google's desktop results (Arial 20px).
     */
    public static function titlePixelWidth(string $title): int
    {
        $width = 0.0;

        foreach (mb_str_split($title) as $character) {
            $width += match (true) {
                str_contains('iljtf!|.,:;\'', $character) => 5.6,
                $character === ' ' => 5.6,
                str_contains('rI()[]-', $character) => 6.7,
                str_contains('mwMW', $character) => 16.7,
                ctype_upper($character) => 13.3,
                ctype_digit($character) => 11.1,
                default => 10.0,
            };
        }

        return (int) round($width);
    }

    /**
     * The record being edited (the page, post, model...), when there is one.
     */
    private static function owner(mixed $livewire): ?Model
    {
        return is_object($livewire) && method_exists($livewire, 'getRecord') && $livewire->getRecord() instanceof Model
            ? $livewire->getRecord()
            : null;
    }

    /**
     * @param  Closure(Get, ?Model): string  $defaultTitle
     */
    private static function title(Get $get, ?Model $owner, Closure $defaultTitle): string
    {
        $site = app(SiteSettings::class);
        $title = filled($get('title')) ? (string) $get('title') : $defaultTitle($get, $owner);

        return str_contains($title, $site->site_name) ? $title : "{$title} {$site->title_separator} {$site->site_name}";
    }

    private static function titleHelper(string $fullTitle): string
    {
        $pixels = self::titlePixelWidth($fullTitle);

        return Str::length($fullTitle).' characters, about '.$pixels.' of '.self::TITLE_PIXEL_LIMIT.' pixels (including the site name)'
            .($pixels > self::TITLE_PIXEL_LIMIT ? '. Google will probably cut it short.' : '.');
    }

    private static function serpPreview(string $title, string $description, ?string $url): HtmlString
    {
        $displayUrl = $url !== null ? str_replace(['https://', 'http://'], '', $url) : str_replace(['https://', 'http://'], '', url('/'));
        $crumbs = implode(' › ', array_filter(explode('/', $displayUrl)));

        return new HtmlString(
            '<div style="max-width:600px;font-family:Arial,sans-serif;padding:12px 14px;border:1px solid rgb(226 232 240);border-radius:12px;background:#fff">'
            .'<div style="font-size:12px;color:#4d5156">'.e($crumbs).'</div>'
            .'<div style="font-size:20px;line-height:1.3;color:#1a0dab;margin-top:2px">'.e(Str::limit($title, 70)).'</div>'
            .'<div style="font-size:14px;line-height:1.58;color:#4d5156;margin-top:3px">'.e(Str::limit($description, 160)).'</div>'
            .'</div>'
        );
    }

    private static function checklist(string $title, string $description, string $keyword, ?string $content, bool $noindex): HtmlString
    {
        $keyword = trim(mb_strtolower($keyword));
        $titleLength = Str::length($title);
        $descriptionLength = Str::length($description);

        $checks = [
            [$titleLength >= 30 && $titleLength <= 65, "Title is {$titleLength} characters (aim for 30–65)"],
            [$descriptionLength >= 70 && $descriptionLength <= 160, "Description is {$descriptionLength} characters (aim for 70–160)"],
            [! $noindex, $noindex ? 'Page is hidden from search engines' : 'Page can appear in search results'],
        ];

        if ($keyword === '') {
            $checks[] = [false, 'Add a focus keyword to check the title, description and content'];
        } else {
            $checks[] = [str_contains(mb_strtolower($title), $keyword), 'Focus keyword in the title'];
            $checks[] = [str_contains(mb_strtolower($description), $keyword), 'Focus keyword in the description'];

            if ($content !== null) {
                $checks[] = [str_contains(mb_strtolower($content), $keyword), 'Focus keyword in the page content'];
            }
        }

        if ($content !== null) {
            $words = Blocks::wordCount($content);
            $checks[] = [$words >= 300, "{$words} words of content (aim for 300 or more)"];
        }

        $html = '<ul style="display:grid;gap:4px;font-size:14px">';

        foreach ($checks as [$passed, $label]) {
            $html .= '<li>'.($passed ? '<span style="color:#15803D;font-weight:700">✓</span> ' : '<span style="color:#B45309;font-weight:700">!</span> ').e($label).'</li>';
        }

        return new HtmlString($html.'</ul>');
    }
}
