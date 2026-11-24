<?php

namespace App\Http\Controllers;

use App\Support\Seo\Indexing;
use Illuminate\Http\Response;

/**
 * robots.txt. Blocks everything unless indexing is allowed (production and
 * switched on in settings). The admin is always disallowed.
 */
class RobotsController extends Controller
{
    public function __invoke(Indexing $indexing): Response
    {
        $lines = ['User-agent: *'];

        if ($indexing->isAllowed()) {
            $lines[] = 'Disallow: /'.trim(config('zr.admin.path'), '/');
            $lines[] = 'Disallow: /livewire';

            // Private, signed customer pages (they are also noindex).
            $lines[] = 'Disallow: /book-repair/thank-you';
            $lines[] = 'Disallow: /book-repair/calendar/';
            $lines[] = 'Disallow: /track-repair/';
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
