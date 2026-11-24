<?php

namespace App\Http\Controllers;

use App\Models\ConsentRecord;
use App\Services\Attribution\AttributionTracker;
use App\Services\Tracking\ConsentState;
use App\Settings\ConsentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * POST /consent: records the visitor's cookie choice (UK GDPR / PECR proof)
 * and sets the consent cookie the browser script reads.
 */
class ConsentController extends Controller
{
    public function __invoke(Request $request, ConsentSettings $settings, AttributionTracker $attribution): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in([ConsentRecord::ACCEPT_ALL, ConsentRecord::REJECT_ALL, ConsentRecord::CUSTOM])],
            'analytics' => ['required', 'boolean'],
            'marketing' => ['required', 'boolean'],
            'page' => ['nullable', 'string', 'max:500'],
        ]);

        $previous = ConsentState::fromRequest($request);

        $state = new ConsentState(
            decided: true,
            analytics: $validated['action'] === ConsentRecord::ACCEPT_ALL || ($validated['action'] === ConsentRecord::CUSTOM && (bool) $validated['analytics']),
            marketing: $validated['action'] === ConsentRecord::ACCEPT_ALL || ($validated['action'] === ConsentRecord::CUSTOM && (bool) $validated['marketing']),
            consentId: $previous->consentId ?? (string) Str::uuid(),
            version: $settings->policy_version,
        );

        ConsentRecord::query()->create([
            'consent_id' => $state->consentId,
            'action' => $validated['action'],
            'analytics' => $state->analytics,
            'marketing' => $state->marketing,
            'policy_version' => $state->version,
            'ip_hash' => AttributionTracker::hashIp($request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
            'page' => isset($validated['page']) ? Str::limit((string) parse_url($validated['page'], PHP_URL_PATH), 490, '') : null,
        ]);

        if ($state->marketing) {
            $attribution->remember((array) $request->session()->get(AttributionTracker::SESSION_KEY, []));
        } else {
            $attribution->forget();
        }

        return response()
            ->json(['analytics' => $state->analytics, 'marketing' => $state->marketing])
            ->withCookie(cookie(
                ConsentState::COOKIE,
                $state->cookieValue(),
                ConsentState::LIFETIME_DAYS * 24 * 60,
                path: '/',
                secure: $request->isSecure(),
                httpOnly: false,
                raw: true,
                sameSite: 'lax',
            ));
    }
}
