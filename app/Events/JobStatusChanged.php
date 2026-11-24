<?php

namespace App\Events;

use App\Models\JobStatusChange;
use App\Models\RepairJob;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A repair job moved to a new status.
 */
class JobStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public RepairJob $job, public JobStatusChange $change) {}
}
