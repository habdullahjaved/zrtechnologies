<?php

namespace App\Services\Seo;

use App\Models\NotFoundLog;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Redirects and the 404 log.
 *
 * Redirects are only looked up when a request would otherwise be "page not
 * found", so normal page views never pay for the database lookup.
 */
class RedirectManager
{
    /**
     * Requests that are never logged as missing pages: assets, machine
     * endpoints and the admin.
     *
     * @var list<string>
     */
    private const array IGNORED_PATTERNS = ['build/*', 'storage/*', 'livewire*', 'favicon*', '*.php', '.well-known/*', 'wp-*', '*/wp-*'];

    /**
     * Point the old address at the new one when a page moves, without chains
     * or loops.
     */
    public function moved(string $from, string $to): void
    {
        $from = Redirect::normalisePath($from);
        $to = Redirect::normalisePath($to);

        if ($from === $to) {
            return;
        }

        // The new address is a real page now, so nothing may redirect away from it.
        Redirect::query()->where('from_path', $to)->delete();

        // Anything that pointed at the old address now points straight at the new one.
        Redirect::query()->where('to_path', $from)->update(['to_path' => $to]);

        Redirect::query()->updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => 301, 'is_active' => true, 'is_automatic' => true],
        );
    }

    /**
     * Handle a request that matched no page: follow a redirect if there is one,
     * otherwise log the address. Returns null to show the normal 404 page.
     */
    public function handleMissing(Request $request): ?RedirectResponse
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return null;
        }

        $path = Redirect::normalisePath($request->getPathInfo());

        if ($this->isIgnored($path)) {
            return null;
        }

        $redirect = Redirect::query()->where('from_path', $path)->where('is_active', true)->first();

        if ($redirect !== null) {
            $redirect->newQuery()->whereKey($redirect->getKey())->update(['hits' => $redirect->hits + 1, 'last_hit_at' => now()]);

            $target = $redirect->targetUrl();
            $query = $request->getQueryString();

            return redirect()->to($query !== null && ! str_contains($target, '?') ? $target.'?'.$query : $target, $redirect->status_code);
        }

        $this->logMissing($path, $request);

        return null;
    }

    private function logMissing(string $path, Request $request): void
    {
        $referrer = $request->headers->get('referer');

        $log = NotFoundLog::query()->firstOrNew(['path' => Str::limit($path, 490, '')]);
        $log->hits = $log->exists ? $log->hits + 1 : 1;
        $log->last_seen_at = now();
        $log->last_referrer = $referrer !== null ? Str::limit($referrer, 490, '') : $log->last_referrer;
        $log->last_user_agent = Str::limit((string) $request->userAgent(), 290, '');
        $log->save();
    }

    private function isIgnored(string $path): bool
    {
        $relative = ltrim($path, '/');
        $admin = trim((string) config('zr.admin.path'), '/');

        return Str::is([...self::IGNORED_PATTERNS, $admin, $admin.'/*'], $relative);
    }
}
