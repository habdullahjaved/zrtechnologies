<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapBuilder;
use Illuminate\Http\Response;

/**
 * /sitemap.xml (the index) and /sitemaps/{type}.xml.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        return response()
            ->view('sitemaps.index', ['types' => SitemapBuilder::TYPES])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function show(string $type, SitemapBuilder $sitemaps): Response
    {
        abort_unless(in_array($type, SitemapBuilder::TYPES, true), 404);

        return response()
            ->view('sitemaps.urlset', ['entries' => $sitemaps->entries($type)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
