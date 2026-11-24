<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Enums\SystemPage;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\RedirectsOnSlugChange;
use App\Models\Contracts\HasPublicPage;
use App\Support\Content\Blocks;
use App\Support\Content\SystemPageDefaults;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A website page. System pages (with a key) have fixed addresses; custom
 * pages are served at /{slug}.
 *
 * @property SystemPage|null $key
 * @property PublishStatus $status
 * @property Carbon|null $published_at
 * @property list<array{type: string, data: array<string, mixed>}>|null $blocks
 */
#[Fillable(['key', 'title', 'slug', 'eyebrow', 'intro', 'blocks', 'status', 'published_at', 'needs_legal_review', 'show_in_sitemap'])]
class Page extends Model implements HasPublicPage
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasFaqs, HasSeoMeta, LogsActivity, RedirectsOnSlugChange, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => SystemPage::class,
            'blocks' => 'array',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
            'needs_legal_review' => 'boolean',
            'show_in_sitemap' => 'boolean',
            'word_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // System pages have fixed addresses that the site relies on.
        static::deleting(fn (self $page): bool => ! $page->isSystem());

        static::saving(fn (self $page) => $page->fillDerivedAttributes());
    }

    /**
     * Word count, and the publish date for a page published without one.
     */
    public function fillDerivedAttributes(): void
    {
        $this->word_count = Blocks::wordCount(Blocks::plainText($this->blocks).' '.$this->intro);

        if ($this->status === PublishStatus::Published && $this->published_at === null) {
            $this->published_at = now();
        }
    }

    /**
     * The stored row for a system page, or an unsaved page filled with the
     * default wording when it has not been created yet.
     */
    public static function system(SystemPage $key): self
    {
        $page = self::query()->with('seoMeta')->where('key', $key->value)->first();

        return $page ?? SystemPageDefaults::make($key);
    }

    /**
     * Published pages whose publish date has passed.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->whereIn('status', PublishStatus::live())
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isLive(): bool
    {
        return in_array($this->status, PublishStatus::live(), true)
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function isSystem(): bool
    {
        return $this->key !== null;
    }

    public function url(): string
    {
        if ($this->key !== null && Route::has($this->key->routeName())) {
            return route($this->key->routeName());
        }

        return url($this->slug);
    }

    public function pathForSlug(string $slug): ?string
    {
        return $this->key === null ? '/'.$slug : null;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isLive();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'status', 'published_at', 'needs_legal_review'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('content');
    }
}
