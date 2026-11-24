<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers for every web response, plus a Content-Security-Policy on
 * public pages.
 *
 * The CSP uses a fresh nonce per response and 'strict-dynamic': our own
 * scripts carry the nonce, and the tags they load (Google, Meta, Clarity)
 * are trusted through them, so no third-party domains need listing.
 * 'unsafe-eval' is needed by Alpine.js. The admin panel is excluded because
 * Filament relies on inline scripts.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $useCsp = (bool) config('zr.csp.enabled') && ! $this->isAdmin($request);

        if ($useCsp) {
            Vite::useCspNonce();
        }

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($useCsp && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $header = config('zr.csp.report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            $response->headers->set($header, $this->policy((string) Vite::cspNonce(), $request));
        }

        return $response;
    }

    private function policy(string $nonce, Request $request): string
    {
        $devServer = $this->viteDevServer();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", "'strict-dynamic'", "'unsafe-eval'", 'https:'],
            'style-src' => ["'self'", "'unsafe-inline'", ...($devServer ? [$devServer] : [])],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'", 'https:', ...($devServer ? [$devServer, Str::replaceFirst('http', 'ws', $devServer)] : [])],
            'media-src' => ["'self'", 'blob:', 'https:'],
            'frame-src' => ["'self'", 'https://www.youtube-nocookie.com', 'https://www.openstreetmap.org', 'https://www.googletagmanager.com', 'https://td.doubleclick.net', 'https://www.facebook.com'],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ];

        $policy = collect($directives)
            ->map(fn (array $sources, string $directive): string => $directive.' '.implode(' ', $sources))
            ->implode('; ');

        return app()->isProduction() && $request->isSecure() ? $policy.'; upgrade-insecure-requests' : $policy;
    }

    /**
     * The Vite dev server origin while `npm run dev` is running locally.
     */
    private function viteDevServer(): ?string
    {
        $hot = public_path('hot');

        return is_file($hot) ? rtrim(trim((string) file_get_contents($hot)), '/') : null;
    }

    private function isAdmin(Request $request): bool
    {
        $admin = trim((string) config('zr.admin.path'), '/');

        return $request->is($admin, $admin.'/*');
    }
}
