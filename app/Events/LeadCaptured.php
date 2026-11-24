<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new enquiry arrived (not a completed booking: see BookingCreated).
 */
class LeadCaptured implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Lead $lead) {}
}
