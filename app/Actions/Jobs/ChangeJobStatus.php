<?php

namespace App\Actions\Jobs;

use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Events\JobStatusChanged;
use App\Models\JobStatusChange;
use App\Models\RepairJob;
use App\Models\User;
use App\Support\Messaging\MessageRenderer;
use App\Support\Messaging\MessageTemplateCatalogue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves a repair job to its next status: records the history, stamps the
 * milestone, keeps the lead's pipeline stage in step and (optionally)
 * emails the customer.
 */
final class ChangeJobStatus
{
    public function __construct(private readonly MessageRenderer $messages) {}

    /**
     * @throws ValidationException when the move is not allowed from the current status.
     */
    public function handle(
        RepairJob $job,
        JobStatus $to,
        ?User $user = null,
        ?string $note = null,
        ?string $customerMessage = null,
        bool $notifyCustomer = true,
    ): JobStatusChange {
        $from = $job->status;

        if (! $from->canMoveTo($to)) {
            throw ValidationException::withMessages([
                'status' => "A job that is \"{$from->getLabel()}\" cannot move to \"{$to->getLabel()}\".",
            ]);
        }

        return DB::transaction(function () use ($job, $from, $to, $user, $note, $customerMessage, $notifyCustomer): JobStatusChange {
            $now = now();
            $job->status = $to;

            if (($column = $to->milestoneColumn()) !== null && $job->{$column} === null) {
                $job->{$column} = $now;
            }

            if ($from === JobStatus::Diagnosing && $job->diagnosed_at === null) {
                $job->diagnosed_at = $now;
            }

            if ($to === JobStatus::Completed) {
                $job->final_price_pence ??= $job->estimatedTotalPence();
                $job->paid_at ??= $now;
            }

            if (in_array($to, [JobStatus::Returned, JobStatus::Completed], true) && $job->warranty_expires_at === null) {
                $warrantyDays = (int) $job->items()->max('warranty_days');

                if ($warrantyDays > 0) {
                    $job->warranty_expires_at = ($job->returned_at ?? $now)->copy()->addDays($warrantyDays);
                }
            }

            $job->save();

            $change = JobStatusChange::query()->create([
                'repair_job_id' => $job->getKey(),
                'from_status' => $from,
                'to_status' => $to,
                'note' => filled($note) ? $note : null,
                'customer_message' => filled($customerMessage) ? $customerMessage : null,
                'user_id' => $user?->getKey(),
                'customer_notified' => $notifyCustomer && $this->canNotify($job, $to),
            ]);

            $this->syncLead($job, $to);

            event(new JobStatusChanged($job, $change));

            return $change;
        });
    }

    /**
     * Whether a customer email exists for this status and the customer has an email address.
     */
    public function canNotify(RepairJob $job, JobStatus $to): bool
    {
        $job->loadMissing('customer');

        return filled($job->customer->email)
            && $this->messages->template(MessageTemplateCatalogue::jobStatusKey($to)) !== null;
    }

    private function syncLead(RepairJob $job, JobStatus $to): void
    {
        $job->loadMissing('lead');
        $lead = $job->lead;

        if ($lead === null) {
            return;
        }

        $status = match ($to) {
            JobStatus::Booked, JobStatus::CollectionScheduled => LeadStatus::Booked,
            JobStatus::Collected => LeadStatus::Collected,
            JobStatus::Completed => LeadStatus::Completed,
            JobStatus::Cancelled => LeadStatus::Lost,
            default => null,
        };

        if ($status === null || $lead->status === $status) {
            return;
        }

        $lead->status = $status;

        if ($status === LeadStatus::Completed) {
            $lead->final_value_pence = $job->final_price_pence;
        }

        if ($status === LeadStatus::Lost) {
            $lead->lost_reason ??= 'Booking cancelled';
        }

        $lead->save();
    }
}
