<?php

namespace App\Http\Controllers;

use App\Actions\Leads\CaptureLead;
use App\Data\LeadData;
use App\Enums\LeadType;
use App\Models\Lead;
use App\Services\Attribution\AttributionTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * POST /t/e: the browser reports a tap on the phone number or the WhatsApp
 * button. It is saved as a lead (with its attribution) and sent server-side
 * to Meta with the same event ID as the browser event, so it counts once.
 *
 * Same-origin only, rate limited, and at most one lead per visitor per type
 * every 30 minutes.
 */
class TrackEventController extends Controller
{
    private const array EVENTS = [
        'click_to_call' => LeadType::CallClick,
        'click_whatsapp' => LeadType::WhatsAppClick,
    ];

    private const int REPEAT_MINUTES = 30;

    public function __invoke(Request $request, CaptureLead $capture, AttributionTracker $attribution): Response
    {
        $validated = $request->validate([
            'event' => ['required', Rule::in(array_keys(self::EVENTS))],
            'event_id' => ['required', 'uuid'],
            'location' => ['nullable', 'string', 'max:60'],
        ]);

        if (! $this->isSameOrigin($request)) {
            return response()->noContent(403);
        }

        $type = self::EVENTS[$validated['event']];
        $sessionKey = 'tracked_clicks.'.$type->value;
        $last = $request->session()->get($sessionKey);

        if (is_int($last) && $last > now()->subMinutes(self::REPEAT_MINUTES)->getTimestamp()) {
            return response()->noContent();
        }

        if (Lead::query()->where('event_id', $validated['event_id'])->exists()) {
            return response()->noContent();
        }

        $capture->handle(
            new LeadData(type: $type, details: array_filter(['location' => $validated['location'] ?? null, 'page' => $this->page($request)])),
            $attribution->current($request),
            $validated['event_id'],
        );

        $request->session()->put($sessionKey, now()->getTimestamp());

        return response()->noContent();
    }

    private function isSameOrigin(Request $request): bool
    {
        $origin = $request->headers->get('origin') ?? $request->headers->get('referer');
        $host = is_string($origin) ? parse_url($origin, PHP_URL_HOST) : null;

        return is_string($host) && mb_strtolower($host) === mb_strtolower($request->getHost());
    }

    private function page(Request $request): ?string
    {
        $referer = $request->headers->get('referer');

        return is_string($referer) ? (string) parse_url($referer, PHP_URL_PATH) : null;
    }
}
