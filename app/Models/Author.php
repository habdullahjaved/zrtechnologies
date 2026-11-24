<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOnSlugChange;
use App\Models\Contracts\HasPublicPage;
use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A blog author with a public bio page, for E-E-A-T and Person structured data.
 *
 * @property list<string|null>|null $social_links
 */
#[Fillable(['user_id', 'name', 'slug', 'role_title', 'bio', 'credentials', 'photo', 'social_links', 'is_active'])]
class Author extends Model implements HasPublicPage
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory, RedirectsOnSlugChange;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function url(): string
    {
        return route('blog.author', $this->slug);
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }

    /**
     * Profile links with a URL set.
     *
     * @return list<string>
     */
    public function profileUrls(): array
    {
        return array_values(array_filter($this->social_links ?? [], fn (?string $url): bool => filled($url)));
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/blog/author/'.$slug;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_active;
    }
}
