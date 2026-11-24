<?php

namespace App\Actions\Leads;

use App\Actions\Customers\FindOrCreateCustomer;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Models\AvailabilityWindow;
use App\Models\JobStatusChange;
use App\Models\Lead;
use App\Models\RepairJob;
use App\Models\User;
use App\Settings\BookingSettings;
use App\Support\Postcode;
use App\Support\ReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff turn an enquiry (phone call, quote, callback…) into a repair job.
 */
final class ConvertLeadToJob
{
    public function __construct(
        private readonly FindOrCreateCustomer $customers,
        private readonly CaptureLead $leads,
        private readonly ReferenceGenerator $references,
        private readonly BookingSettings $settings,
    ) {}

    /**
     * @param  array{device_model_id?: int|null, reported_fault?: string|null, quoted_price_pence?: int|null, collection_fee_pence?: int|null, collection_date?: string|null, availability_window_id?: int|null, technician_id?: int|null, line1?: string|null, line2?: string|null, town?: string|null, postcode?: string|null}  $details
     */
    public function handle(Lead $lead, array $details, ?User $user = null): RepairJob
    {
        if ($lead->repairJob()->exists()) {
            throw ValidationException::withMessages(['lead' => 'This lead already has a repair job.']);
        }

        if (blank($lead->name) || (blank($lead->email) && blank($lead->phone))) {
            throw ValidationException::withMessages(['lead' => 'Add the customer’s name and a phone number or email to the lead first.']);
        }

        return DB::transaction(function () use ($lead, $details, $user): RepairJob {
            $customer = $lead->customer ?? $this->customers->handle(
                ...$this->leads->splitName((string) $lead->name),
                email: $lead->email,
                phone: $lead->phone,
            );

            $address = null;

            if (filled($details['line1'] ?? null) && filled($details['postcode'] ?? null)) {
                $address = $customer->addresses()->create([
                    'line1' => $details['line1'],
                    'line2' => $details['line2'] ?? null,
                    'town' => $details['town'] ?? '',
                    'postcode' => Postcode::normalise($details['postcode']) ?? $details['postcode'],
                    'is_default' => ! $customer->addresses()->exists(),
                ]);
            }

            $window = filled($details['availability_window_id'] ?? null)
                ? AvailabilityWindow::query()->find($details['availability_window_id'])
                : null;

            $job = RepairJob::query()->create([
                'reference' => $this->references->next(ReferenceGenerator::JOB, $this->settings->reference_prefix),
                'status' => JobStatus::Booked,
                'lead_id' => $lead->getKey(),
                'customer_id' => $customer->getKey(),
                'technician_id' => $details['technician_id'] ?? null,
                'service_area_id' => $lead->service_area_id,
                'device_model_id' => $details['device_model_id'] ?? $lead->device_model_id,
                'reported_fault' => $details['reported_fault'] ?? $lead->message,
                'collection_address_id' => $address?->getKey(),
                'collection_date' => $details['collection_date'] ?? null,
                'availability_window_id' => $window?->getKey(),
                'collection_window' => $window?->timeRange(),
                'quoted_price_pence' => (int) ($details['quoted_price_pence'] ?? $lead->estimated_value_pence ?? 0),
                'collection_fee_pence' => (int) ($details['collection_fee_pence'] ?? 0),
                'booked_at' => now(),
            ]);

            JobStatusChange::query()->create([
                'repair_job_id' => $job->getKey(),
                'to_status' => JobStatus::Booked,
                'note' => "Created from lead {$lead->reference}.",
                'user_id' => $user?->getKey(),
            ]);

            $lead->forceFill([
                'customer_id' => $customer->getKey(),
                'status' => LeadStatus::Booked,
                'is_partial' => false,
                'converted_at' => now(),
            ])->save();

            return $job;
        });
    }
}
