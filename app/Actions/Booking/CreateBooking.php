<?php

namespace App\Actions\Booking;

use App\Actions\Customers\FindOrCreateCustomer;
use App\Actions\Leads\CaptureLead;
use App\Data\AttributionData;
use App\Data\BookingData;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Events\BookingCreated;
use App\Models\Address;
use App\Models\AvailabilityWindow;
use App\Models\Customer;
use App\Models\DeviceModel;
use App\Models\JobStatusChange;
use App\Models\Lead;
use App\Models\RepairJob;
use App\Models\RepairPrice;
use App\Models\ServiceArea;
use App\Models\ServiceCategory;
use App\Services\Availability\AvailabilityService;
use App\Settings\BookingSettings;
use App\Support\ReferenceGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns a completed booking into a customer, a lead and a repair job.
 *
 * Prices are looked up again from the live price list and the collection
 * slot is re-checked under a lock, so the browser can never change a price
 * or overbook a window.
 */
final class CreateBooking
{
    public function __construct(
        private readonly FindOrCreateCustomer $customers,
        private readonly CaptureLead $leads,
        private readonly ReferenceGenerator $references,
        private readonly AvailabilityService $availability,
        private readonly BookingSettings $settings,
    ) {}

    /**
     * @param  Lead|null  $partialLead  The lead saved while the customer was filling in the form, if any.
     *
     * @throws ValidationException when the slot has gone or a repair is no longer offered.
     */
    public function handle(BookingData $data, AttributionData $attribution, ?Lead $partialLead = null): RepairJob
    {
        return DB::transaction(function () use ($data, $attribution, $partialLead): RepairJob {
            [$date, $windowId] = $this->parseSlotKey($data->slotKey);

            // Lock the window so two customers cannot take its last place at once.
            $window = AvailabilityWindow::query()->lockForUpdate()->find($windowId);
            $slot = $window !== null ? $this->availability->findSlot($data->slotKey) : null;

            if ($slot === null) {
                throw ValidationException::withMessages(['slot' => 'Sorry, that collection slot has just been taken. Please choose another.']);
            }

            $model = DeviceModel::query()->findOrFail($data->deviceModelId);
            $area = ServiceArea::query()->findOrFail($data->serviceAreaId);
            $prices = $this->prices($model, $data->repairs);

            $customer = $this->customers->handle($data->firstName, $data->lastName, $data->email, $data->phone);
            $customer->recordMarketingConsent($data->marketingConsent || $customer->marketing_consent, 'booking');
            $customer->save();

            $collection = $this->address($customer, $data->collectionAddress, isDefault: true);
            $return = $data->returnAddress !== null ? $this->address($customer, $data->returnAddress, isDefault: false) : null;

            $repairsPence = $prices->sum(fn (RepairPrice $price): int => $price->effectivePricePence());
            $lead = $this->lead($data, $attribution, $partialLead, $customer, $model, $area, $prices, $repairsPence + $area->collection_fee_pence);

            $now = now();

            $job = RepairJob::query()->create([
                'reference' => $this->references->next(ReferenceGenerator::JOB, $this->settings->reference_prefix),
                'status' => JobStatus::Booked,
                'lead_id' => $lead->getKey(),
                'customer_id' => $customer->getKey(),
                'service_area_id' => $area->getKey(),
                'device_model_id' => $model->getKey(),
                'device_colour' => $data->deviceColour,
                'device_storage' => $data->deviceStorage,
                'reported_fault' => $prices->map(fn (RepairPrice $price): string => $price->repairType->name)->implode(', '),
                'customer_notes' => $data->notes,
                'collection_address_id' => $collection->getKey(),
                'return_address_id' => $return?->getKey(),
                'collection_date' => $date,
                'availability_window_id' => $window->getKey(),
                'collection_window' => $window->timeRange(),
                'quoted_price_pence' => $repairsPence,
                'collection_fee_pence' => $area->collection_fee_pence,
                'terms_accepted_at' => $now,
                'passcode_policy_accepted_at' => $now,
                'start_within_cancellation_period' => $data->startWithinCancellationPeriod,
                'booked_at' => $now,
            ]);

            foreach ($prices->values() as $index => $price) {
                $job->items()->create([
                    'repair_type_id' => $price->repair_type_id,
                    'part_quality_id' => $price->part_quality_id,
                    'description' => $price->repairType->name.($price->partQuality ? ' ('.$price->partQuality->name.')' : ''),
                    'price_pence' => $price->effectivePricePence(),
                    'warranty_days' => $price->warranty_days,
                    'sort' => $index,
                ]);
            }

            JobStatusChange::query()->create([
                'repair_job_id' => $job->getKey(),
                'to_status' => JobStatus::Booked,
                'note' => 'Booked online.',
                'customer_notified' => true,
            ]);

            event(new BookingCreated($job));

            return $job;
        });
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function parseSlotKey(string $key): array
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2}):(\d+)$/', $key, $matches) !== 1) {
            throw ValidationException::withMessages(['slot' => 'Please choose a collection slot.']);
        }

        return [$matches[1], (int) $matches[2]];
    }

    /**
     * The live prices for the chosen repairs, in the order chosen.
     *
     * @param  list<array{repair_type_id: int, part_quality_id: int|null}>  $repairs
     * @return Collection<int, RepairPrice>
     */
    private function prices(DeviceModel $model, array $repairs): Collection
    {
        if ($repairs === []) {
            throw ValidationException::withMessages(['repairs' => 'Please choose at least one repair.']);
        }

        $available = RepairPrice::query()
            ->available()
            ->where('device_model_id', $model->getKey())
            ->with(['repairType', 'partQuality'])
            ->get();

        return collect($repairs)->map(function (array $choice) use ($available): RepairPrice {
            $price = $available->first(fn (RepairPrice $price): bool => $price->repair_type_id === $choice['repair_type_id']
                && $price->part_quality_id === $choice['part_quality_id']);

            if ($price === null) {
                throw ValidationException::withMessages(['repairs' => 'One of the repairs you chose is no longer available for this model. Please check your choices.']);
            }

            return $price;
        });
    }

    /**
     * Reuses a matching saved address rather than creating duplicates.
     *
     * @param  array{line1: string, line2: string|null, town: string, postcode: string}  $address
     */
    private function address(Customer $customer, array $address, bool $isDefault): Address
    {
        $saved = $customer->addresses()->firstOrNew([
            'line1' => $address['line1'],
            'postcode' => $address['postcode'],
        ]);

        $saved->fill(['line2' => $address['line2'], 'town' => $address['town']]);

        if ($isDefault) {
            $customer->addresses()->whereKeyNot($saved->getKey() ?? 0)->update(['is_default' => false]);
            $saved->is_default = true;
        }

        $saved->save();

        return $saved;
    }

    /**
     * Completes the lead saved during the form, or creates one.
     *
     * @param  Collection<int, RepairPrice>  $prices
     */
    private function lead(BookingData $data, AttributionData $attribution, ?Lead $partialLead, Customer $customer, DeviceModel $model, ServiceArea $area, Collection $prices, int $estimatePence): Lead
    {
        $attributes = [
            'type' => LeadType::Booking,
            'status' => LeadStatus::Booked,
            'is_partial' => false,
            'customer_id' => $customer->getKey(),
            'service_category_id' => ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->value('id'),
            'name' => trim($data->firstName.' '.$data->lastName),
            'email' => $data->email,
            'phone' => $data->phone,
            'postcode' => $data->postcode,
            'device_model_id' => $model->getKey(),
            'repair_type_id' => $prices->first()?->repair_type_id,
            'service_area_id' => $area->getKey(),
            'message' => $data->notes,
            'estimated_value_pence' => $estimatePence,
            'marketing_consent' => $data->marketingConsent,
            'converted_at' => now(),
        ];

        if ($partialLead !== null && $partialLead->exists && $partialLead->is_partial) {
            $partialLead->fill($attributes)->save();

            return $partialLead;
        }

        return Lead::query()->create([
            ...$attribution->toLeadAttributes(),
            ...$attributes,
            'reference' => $this->leads->nextReference(),
            'event_id' => (string) Str::uuid(),
        ]);
    }
}
