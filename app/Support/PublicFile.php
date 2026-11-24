<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Turns a stored image or file reference into a public URL.
 *
 * Supplied site assets are stored as paths inside /public (images/..., brand/...);
 * admin uploads are stored as paths on the "public" disk (served from /storage).
 */
final class PublicFile
{
    /** @var list<string> */
    private const array SITE_ASSET_PREFIXES = ['images/', 'brand/', 'build/', 'storage/', 'fonts/'];

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset(self::publicPath($path));
    }

    /**
     * The path relative to /public.
     */
    public static function publicPath(string $path): string
    {
        $path = ltrim($path, '/');

        return Str::startsWith($path, self::SITE_ASSET_PREFIXES) ? $path : 'storage/'.$path;
    }
}
