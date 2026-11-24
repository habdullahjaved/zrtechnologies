<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Illuminate\Http\Request;

/**
 * Filament's "MFA set up?" check, but honouring config('zr.admin.require_mfa')
 * on every request rather than only when routes are registered.
 */
class EnsureStaffMultiFactorAuthentication extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! config('zr.admin.require_mfa')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
