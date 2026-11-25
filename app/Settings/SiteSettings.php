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

    /*
     * Title patterns for generated pages. Placeholders: {name}, {model},
     * {repair}, {town}, {title}, {price} ("from" price, e.g. £49).
     */
    public string $title_pattern_service;

    public string $title_pattern_model;

    public string $title_pattern_model_repair;

    public string $title_pattern_location;

    public string $title_pattern_post;

    public string $title_pattern_post_category;

    /** Extra robots.txt lines, added when indexing is allowed. */
    public ?string $robots_extra;

    /** Let search engines index blog tag pages (off by default to avoid index bloat). */
    public bool $index_tag_pages;

    /** Unique words a location page needs before it may be indexed. */
    public int $min_unique_words_location;

    /** Unique words a model × repair page needs before it may be indexed. */
    public int $min_unique_words_model_repair;

    /** Pages more similar than this to a sibling stay noindex. */
    public int $max_similarity_percent;

    /** Show the "coming soon" teaser for future services on the home page. */
    public bool $show_coming_soon;

    /** Google Search Console HTML-tag verification code. */
    public ?string $google_site_verification;

    /** Bing Webmaster Tools verification code (msvalidate.01). */
    public ?string $bing_site_verification;

    /** Pinterest website claim code (p:domain_verify). */
    public ?string $pinterest_site_verification;

    /** Meta Business Manager domain verification code. */
    public ?string $facebook_domain_verification;

    /**
     * Verification meta tags for the page head, only those filled in.
     *
     * @return array<string, string>
     */
    public function verificationTags(): array
    {
        return array_filter([
            'google-site-verification' => $this->google_site_verification,
            'msvalidate.01' => $this->bing_site_verification,
            'p:domain_verify' => $this->pinterest_site_verification,
            'facebook-domain-verification' => $this->facebook_domain_verification,
        ], filled(...));
    }

    public static function group(): string
    {
        return 'site';
    }
}
