<?php

namespace App\Http\Middleware;

use App\Services\Tracking\ConsentState;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;
use Illuminate\Support\Str;

/**
 * Laravel encrypts every cookie it sets and drops any it cannot decrypt.
 * Cookies written by browser scripts (Google, Meta) and the consent cookie
 * (read by our own script) must be left as they are.
 */
class EncryptCookies extends Middleware
{
    /**
     * Name prefixes of cookies set by analytics and advertising scripts:
     * _ga and _ga_{stream} (Google Analytics), _gcl_* (Google Ads), _fbp and _fbc (Meta).
     *
     * @var list<string>
     */
    private const array SCRIPT_COOKIE_PREFIXES = ['_ga', '_gcl_', '_fbp', '_fbc', '_ttp'];

    public function isDisabled($name): bool
    {
        return $name === ConsentState::COOKIE
            || Str::startsWith($name, self::SCRIPT_COOKIE_PREFIXES)
            || parent::isDisabled($name);
    }
}
