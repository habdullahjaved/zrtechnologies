<?php

namespace App\Data;

use App\Enums\LeadType;

/**
 * What a visitor (or staff member) told us in an enquiry.
 */
final readonly class LeadData
{
    /**
     * @param  array<string, mixed>  $details  Extra form answers, e.g. ['callback_time' => 'Morning'].
     */
    public function __construct(
        public LeadType $type,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $postcode = null,
        public ?string $companyName = null,
        public ?int $deviceModelId = null,
        public ?int $repairTypeId = null,
        public ?int $serviceAreaId = null,
        public ?string $message = null,
        public array $details = [],
        public bool $marketingConsent = false,
        public ?int $estimatedValuePence = null,
    ) {}
}
