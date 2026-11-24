<?php

namespace App\Http\Middleware;

use App\Services\Cache\PageCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves public pages from the full-page cache for anonymous visitors.
 *
 * Staff who are signed in, requests with filters or search parameters, and
 * requests carrying flashed messages or validation errors always get a fresh
 * page. Only successful HTML responses are stored.
 */
class CachePublicPage
{
    public function __construct(private readonly PageCache $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->cacheKey($request);

        if ($key === null) {
            return $next($request);
        }

        $token = (string) $request->session()->token();

        if (($cached = $this->cache->get($key, $token)) !== null) {
            return $cached;
        }

        $response = $next($request);

        if ($this->isStorable($response)) {
            $this->cache->put($key, $response, $token);
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }

    private function cacheKey(Request $request): ?string
    {
        if (! $this->cache->isEnabled() || ! $request->isMethod('GET') || $request->user() !== null) {
            return null;
        }

        $session = $request->session();

        // Flashed data (messages, errors, old input) is aged into "_flash.old" on the request that shows it.
        if ($session->get('_flash.old', []) !== [] || $session->has('errors')) {
            return null;
        }

        return $this->cache->key($request);
    }

    private function isStorable(Response $response): bool
    {
        return $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && $response->getContent() !== false;
    }
}
