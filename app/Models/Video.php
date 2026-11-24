<?php

namespace App\Models;

use App\Enums\VideoSource;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\RedirectsOnSlugChange;
use App\Models\Contracts\HasPublicPage;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A short video. Placeholders show a branded "coming soon" card and have no
 * page of their own until a real video is added.
 *
 * @property VideoSource $source
 * @property Carbon|null $published_at
 */
#[Fillable(['title', 'slug', 'description', 'source', 'external_url', 'file_path', 'poster_path', 'duration_seconds', 'transcript', 'service_id', 'is_placeholder', 'placeholder_label', 'is_active', 'show_on_home', 'sort', 'published_at'])]
class Video extends Model implements HasPublicPage
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory, HasSeoMeta, RedirectsOnSlugChange, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => VideoSource::class,
            'duration_seconds' => 'integer',
            'is_placeholder' => 'boolean',
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $video): void {
            if (! $video->is_placeholder && $video->published_at === null) {
                $video->published_at = now();
            }
        });
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    /**
     * Real videos with their own page.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function playable(Builder $query): void
    {
        $query->where('is_active', true)->where('is_placeholder', false);
    }

    public function isPlayable(): bool
    {
        return $this->is_active && ! $this->is_placeholder;
    }

    /**
     * The YouTube video ID from a Shorts, watch or youtu.be link.
     */
    public function youTubeId(): ?string
    {
        if ($this->source !== VideoSource::YouTube || blank($this->external_url)) {
            return null;
        }

        preg_match('~(?:youtube\.com/(?:shorts/|watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $this->external_url, $matches);

        return $matches[1] ?? null;
    }

    public function embedUrl(): ?string
    {
        $id = $this->youTubeId();

        return $id ? "https://www.youtube-nocookie.com/embed/{$id}?autoplay=1&rel=0&playsinline=1" : null;
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }

    /**
     * Poster image: the uploaded one, else the YouTube thumbnail.
     */
    public function posterUrl(): ?string
    {
        if (filled($this->poster_path)) {
            return asset('storage/'.$this->poster_path);
        }

        $id = $this->youTubeId();

        return $id ? "https://i.ytimg.com/vi/{$id}/hqdefault.jpg" : null;
    }

    /**
     * ISO 8601 duration for structured data, e.g. PT45S.
     */
    public function isoDuration(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        return 'PT'.($minutes > 0 ? "{$minutes}M" : '').($seconds > 0 ? "{$seconds}S" : '');
    }

    public function url(): ?string
    {
        return $this->isPlayable() ? route('videos.show', $this->slug) : null;
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/videos/'.$slug;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isPlayable();
    }
}
