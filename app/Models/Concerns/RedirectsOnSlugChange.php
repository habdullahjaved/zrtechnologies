<?php

namespace App\Models\Concerns;

use App\Models\Contracts\HasPublicPage;
use App\Services\Seo\RedirectManager;
use Illuminate\Database\Eloquent\Model;

/**
 * When a public record's slug changes, the old address 301-redirects to the
 * new one so links and search rankings are kept.
 *
 * @mixin HasPublicPage
 */
trait RedirectsOnSlugChange
{
    public static function bootRedirectsOnSlugChange(): void
    {
        // "updated" fires before the original attributes are synced, so the old slug is still available.
        static::updated(function (HasPublicPage $model): void {
            /** @var Model&HasPublicPage $model */
            if (! $model->wasChanged('slug')) {
                return;
            }

            $oldSlug = (string) $model->getRawOriginal('slug');
            $from = $model->pathForSlug($oldSlug);
            $to = $model->pathForSlug((string) $model->getAttribute('slug'));

            if ($from !== null && $to !== null) {
                app(RedirectManager::class)->moved($from, $to);
            }
        });
    }
}
