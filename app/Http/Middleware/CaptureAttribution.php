<?php

namespace App\Http\Middleware;

use App\Services\Attribution\AttributionTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records click IDs, UTM tags and the referrer on public page views
 * (not the admin, Livewire updates or machine endpoints).
 */
class CaptureAttribution
{
    public function __construct(private readonly AttributionTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldCapture($request)) {
            $this->tracker->capture($request);
        }

        return $next($request);
    }

    private function shouldCapture(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson() || $request->hasHeader('X-Livewire')) {
            return false;
        }

        $admin = trim((string) config('zr.admin.path'), '/');

        return ! $request->is($admin, $admin.'/*', 'livewire*', 'up', 'robots.txt', 'sitemap*', 'storage/*', 'build/*');
    }
}
