<?php

namespace App\Services\Cache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full-page cache for public pages viewed by anonymous visitors.
 *
 * Pages are stored with the visitor's CSRF token swapped for a placeholder and
 * the current visitor's token is put back when a page is served, so forms and
 * Livewire components keep working. Saving content or settings bumps a
 * version number, which retires every cached page at once; a new front-end
 * build does the same, so cached pages never point at deleted assets.
 */
class PageCache
{
    private const string VERSION_KEY = 'page-cache:version';

    private const string TOKEN_PLACEHOLDER = '__ZR_CSRF_TOKEN__';

    /**
     * Tracking parameters that do not change the page. They are read by the
     * attribution middleware before the cache is consulted.
     *
     * @var list<string>
     */
    public const array IGNORED_QUERY_PARAMETERS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id',
        'gclid', 'gbraid', 'wbraid', 'gad_source', 'fbclid', 'msclkid', 'ttclid', '_gl',
    ];

    public function isEnabled(): bool
    {
        return (bool) config('zr.page_cache.enabled');
    }

    public function key(Request $request): ?string
    {
        $query = array_diff_key($request->query(), array_flip(self::IGNORED_QUERY_PARAMETERS));

        if ($query !== []) {
            return null;
        }

        return 'page-cache:'.$this->version().':'.$this->buildVersion().':'.sha1($request->getPathInfo());
    }

    public function get(string $key, string $csrfToken): ?Response
    {
        $cached = $this->store()->get($key);

        if (! is_array($cached) || ! is_string($cached['content'] ?? null)) {
            return null;
        }

        return response(str_replace(self::TOKEN_PLACEHOLDER, $csrfToken, $cached['content']), 200, [
            'Content-Type' => $cached['content_type'] ?? 'text/html; charset=UTF-8',
            'X-Page-Cache' => 'HIT',
        ]);
    }

    public function put(string $key, Response $response, string $csrfToken): void
    {
        $content = (string) $response->getContent();

        $this->store()->put($key, [
            'content' => $csrfToken !== '' ? str_replace($csrfToken, self::TOKEN_PLACEHOLDER, $content) : $content,
            'content_type' => $response->headers->get('Content-Type'),
        ], (int) config('zr.page_cache.ttl'));
    }

    /**
     * Retire every cached page.
     */
    public function flush(): void
    {
        $store = $this->store();

        if (! $store->has(self::VERSION_KEY)) {
            $store->forever(self::VERSION_KEY, 1);
        }

        $store->increment(self::VERSION_KEY);
    }

    private function version(): int
    {
        return (int) $this->store()->rememberForever(self::VERSION_KEY, fn (): int => 1);
    }

    /**
     * Changes whenever `npm run build` produces new asset file names.
     */
    private function buildVersion(): string
    {
        $manifest = public_path('build/manifest.json');

        return is_file($manifest) ? (string) filemtime($manifest) : 'dev';
    }

    private function store(): Repository
    {
        return Cache::store(config('zr.page_cache.store'));
    }
}
