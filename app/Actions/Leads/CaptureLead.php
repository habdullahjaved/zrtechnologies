<?php

namespace App\Actions\Leads;

use App\Actions\Customers\FindOrCreateCustomer;
use App\Data\AttributionData;
use App\Data\LeadData;
use App\Enums\LeadStatus;
use App\Events\LeadCaptured;
use App\Models\Lead;
use App\Models\ServiceCategory;
use App\Support\ReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Saves an enquiry as a lead with its attribution, links it to the customer
 * (matched by mobile or email) and tells the team.
 */
final class CaptureLead
{
    public const string REFERENCE_PREFIX = 'ENQ';

    public function __construct(
        private readonly ReferenceGenerator $references,
        private readonly FindOrCreateCustomer $customers,
    ) {}

    public function handle(LeadData $data, AttributionData $attribution): Lead
    {
        return DB::transaction(function () use ($data, $attribution): Lead {
            $customer = filled($data->name) && (filled($data->email) || filled($data->phone))
                ? $this->customers->handle(...$this->splitName((string) $data->name), email: $data->email, phone: $data->phone)
                : null;

            if ($customer !== null && $data->marketingConsent) {
                $customer->recordMarketingConsent(true, $data->type->value.'_form');
                $customer->save();
            }

            $lead = Lead::query()->create([
                ...$attribution->toLeadAttributes(),
                ...self::attributesFrom($data),
                'reference' => $this->nextReference(),
                'status' => LeadStatus::New,
                'service_category_id' => ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->value('id'),
                'customer_id' => $customer?->getKey(),
                'event_id' => (string) Str::uuid(),
            ]);

            event(new LeadCaptured($lead));

            return $lead;
        });
    }

    public function nextReference(): string
    {
        return $this->references->next(ReferenceGenerator::LEAD, self::REFERENCE_PREFIX);
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributesFrom(LeadData $data): array
    {
        return [
            'type' => $data->type,
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'postcode' => $data->postcode,
            'company_name' => $data->companyName,
            'device_model_id' => $data->deviceModelId,
            'repair_type_id' => $data->repairTypeId,
            'service_area_id' => $data->serviceAreaId,
            'message' => $data->message,
            'details' => $data->details ?: null,
            'marketing_consent' => $data->marketingConsent,
            'estimated_value_pence' => $data->estimatedValuePence,
        ];
    }

    /**
     * "Sarah Jane Smith" → ["Sarah Jane", "Smith"]; "Sarah" → ["Sarah", null].
     *
     * @return array{firstName: string, lastName: string|null}
     */
    public function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if (count($parts) < 2) {
            return ['firstName' => trim($name), 'lastName' => null];
        }

        $last = array_pop($parts);

        return ['firstName' => implode(' ', $parts), 'lastName' => $last];
    }
}
