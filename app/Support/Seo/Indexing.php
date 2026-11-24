<?php

namespace App\Support\Seo;

use App\Settings\SiteSettings;

/**
 * Decides whether search engines may index the site. Both must be true:
 * the environment is listed in config('zr.indexable_environments') AND
 * indexing is switched on in Website & SEO settings.
 */
final class Indexing
{
    public function __construct(private SiteSettings $site) {}

    public function isAllowed(): bool
    {
        return app()->environment(config('zr.indexable_environments'))
            && $this->site->allow_indexing;
    }
}
