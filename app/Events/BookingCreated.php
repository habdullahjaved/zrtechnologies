<?php

namespace App\Events;

use App\Models\RepairJob;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A customer completed an online booking; the job and its lead are saved.
 */
class BookingCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public RepairJob $job) {}
}
