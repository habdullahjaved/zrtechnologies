<?php

namespace App\Services\Postcodes;

use App\Models\PostcodeLookup;
use App\Support\Postcode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Looks up full UK postcodes on postcodes.io (free, no key), caching every
 * answer in the postcode_lookups table.
 */
class PostcodeLookupService
{
    /**
     * The cached or fresh lookup for a full postcode, or null when postcodes.io
     * could not be reached (callers then fall back to a district-only check).
     */
    public function lookup(string $postcode): ?PostcodeLookup
    {
        $normalised = Postcode::normalise($postcode);

        if ($normalised === null) {
            return null;
        }

        $cached = PostcodeLookup::query()->where('postcode', $normalised)->first();

        if ($cached !== null && $cached->fetched_at->gt(now()->subDays((int) config('services.postcodes_io.cache_days')))) {
            return $cached;
        }

        return $this->fetch($normalised) ?? $cached;
    }

    private function fetch(string $postcode): ?PostcodeLookup
    {
        try {
            $response = Http::baseUrl((string) config('services.postcodes_io.url'))
                ->timeout((int) config('services.postcodes_io.timeout'))
                ->acceptJson()
                ->retry(2, 200, throw: false)
                ->get('/postcodes/'.rawurlencode($postcode));
        } catch (ConnectionException $exception) {
            Log::warning('postcodes.io unreachable', ['postcode' => $postcode, 'error' => $exception->getMessage()]);

            return null;
        }

        if ($response->status() === 404) {
            return $this->store($postcode, ['is_valid' => false]);
        }

        if (! $response->successful()) {
            Log::warning('postcodes.io error', ['postcode' => $postcode, 'status' => $response->status()]);

            return null;
        }

        $result = $response->json('result');

        if (! is_array($result)) {
            return null;
        }

        return $this->store($postcode, [
            'is_valid' => true,
            'admin_district' => $result['admin_district'] ?? null,
            'ward' => $result['admin_ward'] ?? null,
            'latitude' => $result['latitude'] ?? null,
            'longitude' => $result['longitude'] ?? null,
            'payload' => array_intersect_key($result, array_flip([
                'postcode', 'outcode', 'admin_district', 'admin_ward', 'admin_county', 'region', 'country', 'parish', 'latitude', 'longitude',
            ])),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function store(string $postcode, array $attributes): PostcodeLookup
    {
        return PostcodeLookup::query()->updateOrCreate(
            ['postcode' => $postcode],
            [
                'outcode' => Postcode::outcode($postcode),
                'admin_district' => null,
                'ward' => null,
                'latitude' => null,
                'longitude' => null,
                'payload' => null,
                ...$attributes,
                'fetched_at' => now(),
            ],
        );
    }
}
