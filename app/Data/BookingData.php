<?php

namespace App\Data;

/**
 * A completed online booking, as submitted by the wizard. Prices are not
 * included: they are looked up again from the price list when the booking is saved.
 */
final readonly class BookingData
{
    /**
     * @param  list<array{repair_type_id: int, part_quality_id: int|null}>  $repairs
     * @param  array{line1: string, line2: string|null, town: string, postcode: string}  $collectionAddress
     * @param  array{line1: string, line2: string|null, town: string, postcode: string}|null  $returnAddress  Null when returning to the collection address.
     */
    public function __construct(
        public string $postcode,
        public int $serviceAreaId,
        public int $deviceModelId,
        public array $repairs,
        public string $slotKey,
        public string $firstName,
        public ?string $lastName,
        public string $email,
        public string $phone,
        public array $collectionAddress,
        public ?array $returnAddress = null,
        public ?string $deviceColour = null,
        public ?string $deviceStorage = null,
        public ?string $notes = null,
        public bool $marketingConsent = false,
        public bool $startWithinCancellationPeriod = false,
    ) {}
}
