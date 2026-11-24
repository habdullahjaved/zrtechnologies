<?php

namespace App\Models\Contracts;

/**
 * A record with its own public page. The path is used for links, sitemaps and
 * the automatic redirect created when the record's slug changes.
 */
interface HasPublicPage
{
    /**
     * Site-relative path for the given slug, e.g. "/iphone-repair/iphone-15",
     * or null when this record has no page of its own.
     */
    public function pathForSlug(string $slug): ?string;

    /**
     * Whether the page can be visited right now (published, active...).
     */
    public function isPubliclyVisible(): bool;
}
