<?php

namespace App\Data;

use App\Enums\CoverageStatus;
use App\Models\ServiceArea;

/**
 * The answer to "do you collect from my postcode?".
 */
final readonly class CoverageResult
{
    /**
     * @param  string|null  $postcode  Normalised full postcode, or the district when only a district was given.
     * @param  bool  $verified  True when postcodes.io confirmed the postcode; false for district-only or offline checks.
     */
    public function __construct(
        public CoverageStatus $status,
        public ?string $postcode = null,
        public ?string $outcode = null,
        public ?ServiceArea $area = null,
        public bool $verified = false,
    ) {}

    public function isCovered(): bool
    {
        return $this->status === CoverageStatus::Covered;
    }
}
