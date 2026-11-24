<?php

namespace App\Models;

use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\RedirectsOnSlugChange;
use App\Models\Contracts\HasPublicPage;
use Database\Factories\PostCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A blog category, shown at /blog/{slug}.
 */
#[Fillable(['name', 'slug', 'description', 'sort'])]
class PostCategory extends Model implements HasPublicPage
{
    /** @use HasFactory<PostCategoryFactory> */
    use HasFactory, HasSeoMeta, RedirectsOnSlugChange;

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('name');
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
        return true;
    }
}
