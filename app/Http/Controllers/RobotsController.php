<?php

namespace App\Http\Controllers;

use App\Settings\SiteSettings;
use App\Support\Seo\Indexing;
use Illuminate\Http\Response;

/**
 * robots.txt. Blocks everything unless indexing is allowed (production and
 * switched on in settings). The admin, private customer pages and tag pages
 * (unless switched on) are always disallowed. Extra lines can be added in
 * Website & SEO settings.
 */
class RobotsController extends Controller
{
    public function __invoke(Indexing $indexing, SiteSettings $site): Response
    {
        $lines = ['User-agent: *'];

        if ($indexing->isAllowed()) {
            $lines[] = 'Disallow: /'.trim(config('zr.admin.path'), '/');
            $lines[] = 'Disallow: /livewire';

            // Private, signed customer pages (they are also noindex).
            $lines[] = 'Disallow: /book-repair/thank-you';
            $lines[] = 'Disallow: /book-repair/calendar/';
            $lines[] = 'Disallow: /track-repair/';
            $lines[] = 'Disallow: /reviews/leave';
            $lines[] = 'Disallow: /exports/';

            if (! $site->index_tag_pages) {
                $lines[] = 'Disallow: /blog/tag/';
            }

            foreach (preg_split('/\R/', (string) $site->robots_extra) ?: [] as $line) {
                if (trim($line) !== '') {
                    $lines[] = trim($line);
                }
            }

            $lines[] = '';
            $lines[] = 'Sitemap: '.route('sitemap');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
