<?php

namespace App\Actions\Booking;

use App\Actions\Leads\CaptureLead;
use App\Data\AttributionData;
use App\Data\LeadData;
use App\Enums\LeadStatus;
use App\Events\LeadCaptured;
use App\Models\Lead;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;

/**
 * Keeps an unfinished online booking as a lead once the visitor has given a
 * mobile or email, so the team can help if they get stuck (brief §5).
 * The same lead is updated as they continue and completed when they book.
 */
final class SavePartialBooking
{
    public function __construct(private readonly CaptureLead $leads) {}

    public function handle(?Lead $lead, LeadData $data, AttributionData $attribution): Lead
    {
        if ($lead !== null && $lead->exists && $lead->is_partial) {
            $lead->fill(array_filter(CaptureLead::attributesFrom($data), fn (mixed $value): bool => $value !== null && $value !== []))->save();

            return $lead;
        }

        $lead = Lead::query()->create([
            ...$attribution->toLeadAttributes(),
            ...CaptureLead::attributesFrom($data),
            'reference' => $this->leads->nextReference(),
            'status' => LeadStatus::New,
            'is_partial' => true,
            'service_category_id' => ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->value('id'),
            'event_id' => (string) Str::uuid(),
        ]);

        event(new LeadCaptured($lead));

        return $lead;
    }
}
