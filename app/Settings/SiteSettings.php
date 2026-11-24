<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Site-wide SEO defaults, search-engine indexing and legal notices.
 */
class SiteSettings extends Settings
{
    public string $site_name;

    public string $title_separator;

    public string $default_meta_title;

    public string $default_meta_description;

    /** Path relative to /public, e.g. images/og/og-default.jpg. */
    public string $default_og_image;

    /** Master switch: search engines may index the site (only ever honoured in production). */
    public bool $allow_indexing;

    public ?string $announcement;

    public string $trademark_disclaimer;

    public static function group(): string
    {
        return 'site';
    }
}
