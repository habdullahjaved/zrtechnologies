<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\RedirectsOnSlugChange;
use App\Models\Contracts\HasPublicPage;
use App\Support\Content\Blocks;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A blog post or guide. Live when published (or scheduled) and its publish
 * date has passed. Reading time and word count are worked out on save.
 *
 * @property PublishStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $content_updated_at
 * @property list<array{type: string, data: array<string, mixed>}>|null $blocks
 */
#[Fillable(['post_category_id', 'author_id', 'service_id', 'title', 'slug', 'excerpt', 'featured_image', 'featured_image_alt', 'blocks', 'status', 'published_at', 'content_updated_at', 'is_ai_assisted'])]
class Post extends Model implements HasPublicPage
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasFaqs, HasSeoMeta, LogsActivity, RedirectsOnSlugChange, SoftDeletes;

    /** Average adult reading speed used for "x min read". */
    public const int WORDS_PER_MINUTE = 200;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
            'content_updated_at' => 'datetime',
            'reading_minutes' => 'integer',
            'word_count' => 'integer',
            'is_ai_assisted' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (self $post) => $post->fillDerivedAttributes());
    }

    /**
     * Word count, reading time, a publish date for a post published without
     * one, and the "last updated" date when a live post's content changes.
     */
    public function fillDerivedAttributes(): void
    {
        $this->word_count = Blocks::wordCount(Blocks::plainText($this->blocks));
        $this->reading_minutes = max(1, (int) ceil($this->word_count / self::WORDS_PER_MINUTE));

        if (in_array($this->status, PublishStatus::live(), true) && $this->published_at === null) {
            $this->published_at = now();
        }

        if ($this->exists && $this->isDirty(['title', 'blocks', 'excerpt']) && $this->isLive()) {
            $this->content_updated_at = now();
        }
    }

    /**
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    /**
     * @return BelongsTo<Author, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * The service page this post supports, linked from the post.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    /**
     * Hand-picked related posts.
     *
     * @return BelongsToMany<Post, $this>
     */
    public function relatedPosts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'post_related', 'post_id', 'related_post_id')
            ->withPivot('sort')
            ->orderByPivot('sort');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->whereIn('status', PublishStatus::live())
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function latestFirst(Builder $query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    public function isLive(): bool
    {
        return in_array($this->status, PublishStatus::live(), true)
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * When the post was last meaningfully changed (for "Last updated" and dateModified).
     */
    public function lastUpdatedAt(): ?Carbon
    {
        return $this->content_updated_at ?? $this->published_at;
    }

    public function featuredImageUrl(): ?string
    {
        if (blank($this->featured_image)) {
            return null;
        }

        return str_starts_with($this->featured_image, 'images/') ? asset($this->featured_image) : asset('storage/'.$this->featured_image);
    }

    public function url(): string
    {
        return route('blog.show', $this->slug);
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/blog/'.$slug;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isLive();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'status', 'published_at', 'post_category_id', 'author_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('content');
    }
}
