<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOnSlugChange;
use App\Models\Contracts\HasPublicPage;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A blog tag. Its page lists tagged posts and is noindex unless switched on.
 */
#[Fillable(['name', 'slug'])]
class Tag extends Model implements HasPublicPage
{
    /** @use HasFactory<TagFactory> */
    use HasFactory, RedirectsOnSlugChange;

    protected static function booted(): void
    {
        static::saving(function (self $tag): void {
            if (blank($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function url(): string
    {
        return route('blog.tag', $this->slug);
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/blog/tag/'.$slug;
    }

    public function isPubliclyVisible(): bool
    {
        return true;
    }
}
