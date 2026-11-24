<?php

namespace App\Services\Coverage;

use App\Data\CoverageResult;
use App\Enums\CoverageStatus;
use App\Models\ServiceArea;
use App\Services\Postcodes\PostcodeLookupService;
use App\Support\Postcode;
use Illuminate\Database\Eloquent\Collection;

/**
 * Decides whether we collect from a postcode.
 *
 * A postcode is covered when its district (e.g. BB2) belongs to an active
 * service area, or when postcodes.io places it within an area's radius.
 * If postcodes.io is unreachable, the district check alone is used.
 */
class CoverageService
{
    public function __construct(private readonly PostcodeLookupService $lookups) {}

    public function check(?string $input): CoverageResult
    {
        $outcode = Postcode::outcode($input);

        if ($outcode === null) {
            return new CoverageResult(CoverageStatus::Invalid);
        }

        $areas = ServiceArea::query()->listed()->get();
        $postcode = Postcode::normalise($input);

        if ($postcode === null) {
            return $this->result($this->areaForDistrict($areas, $outcode), $outcode, $outcode, verified: false);
        }

        $lookup = $this->lookups->lookup($postcode);

        if ($lookup === null) {
            return $this->result($this->areaForDistrict($areas, $outcode), $postcode, $outcode, verified: false);
        }

        if (! $lookup->is_valid) {
            return new CoverageResult(CoverageStatus::Invalid, $postcode, $outcode);
        }

        $area = $this->areaForDistrict($areas, $outcode);

        if ($area === null && $lookup->latitude !== null && $lookup->longitude !== null) {
            $area = $areas->first(fn (ServiceArea $candidate): bool => $candidate->coversPoint((float) $lookup->latitude, (float) $lookup->longitude));
        }

        return $this->result($area, $postcode, $outcode, verified: true);
    }

    /**
     * @param  Collection<int, ServiceArea>  $areas
     */
    private function areaForDistrict(Collection $areas, string $outcode): ?ServiceArea
    {
        return $areas->first(fn (ServiceArea $area): bool => $area->coversDistrict($outcode));
    }

    private function result(?ServiceArea $area, string $postcode, string $outcode, bool $verified): CoverageResult
    {
        return new CoverageResult(
            status: $area !== null ? CoverageStatus::Covered : CoverageStatus::OutOfArea,
            postcode: $postcode,
            outcode: $outcode,
            area: $area,
            verified: $verified,
        );
    }
}
