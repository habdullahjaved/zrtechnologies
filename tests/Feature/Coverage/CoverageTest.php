<?php

use App\Enums\CoverageStatus;
use App\Models\PostcodeLookup;
use App\Models\ServiceArea;
use App\Services\Coverage\CoverageService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->blackburn = ServiceArea::factory()->districts(['BB1', 'BB2'])->create([
        'name' => 'Blackburn',
        'centre_lat' => 53.7486,
        'centre_lng' => -2.4877,
    ]);
    $this->coverage = app(CoverageService::class);
});

it('covers a postcode in a listed district and caches the lookup', function () {
    Http::fake(['api.postcodes.io/postcodes/*' => Http::response(postcodesIoResult('BB2 4NY'))]);

    $result = $this->coverage->check(' bb24ny ');

    expect($result->status)->toBe(CoverageStatus::Covered)
        ->and($result->postcode)->toBe('BB2 4NY')
        ->and($result->area->is($this->blackburn))->toBeTrue()
        ->and($result->verified)->toBeTrue();

    $lookup = PostcodeLookup::query()->sole();
    expect($lookup->postcode)->toBe('BB2 4NY')
        ->and($lookup->outcode)->toBe('BB2')
        ->and($lookup->admin_district)->toBe('Blackburn with Darwen')
        ->and($lookup->payload)->not->toHaveKey('codes');

    $this->coverage->check('BB2 4NY');
    Http::assertSentCount(1);
});

it('says a real postcode outside every area is out of area', function () {
    Http::fake(['api.postcodes.io/postcodes/*' => Http::response(postcodesIoResult('M1 1AE', 53.4774, -2.2309))]);

    $result = $this->coverage->check('M1 1AE');

    expect($result->status)->toBe(CoverageStatus::OutOfArea)
        ->and($result->area)->toBeNull();
});

it('covers postcodes within an area radius, whatever their district', function () {
    $this->blackburn->update(['radius_km' => 8]);
    Http::fake(['api.postcodes.io/postcodes/*' => Http::response(postcodesIoResult('BB4 4AA', 53.7000, -2.4000))]);

    expect($this->coverage->check('BB4 4AA')->status)->toBe(CoverageStatus::Covered);

    $this->blackburn->update(['radius_km' => 3]);
    PostcodeLookup::query()->delete();

    expect($this->coverage->check('BB4 4AA')->status)->toBe(CoverageStatus::OutOfArea);
});

it('rejects postcodes that postcodes.io says do not exist, and remembers them', function () {
    Http::fake(['api.postcodes.io/postcodes/*' => Http::response(['status' => 404, 'error' => 'Invalid postcode'], 404)]);

    expect($this->coverage->check('BB2 9ZZ')->status)->toBe(CoverageStatus::Invalid)
        ->and(PostcodeLookup::query()->sole()->is_valid)->toBeFalse();
});

it('rejects text that is not a postcode without calling the API', function () {
    Http::fake();

    expect($this->coverage->check('hello')->status)->toBe(CoverageStatus::Invalid)
        ->and($this->coverage->check('')->status)->toBe(CoverageStatus::Invalid);

    Http::assertNothingSent();
});

it('answers district-only checks without calling the API', function () {
    Http::fake();

    $covered = $this->coverage->check('bb1');

    expect($covered->status)->toBe(CoverageStatus::Covered)
        ->and($covered->postcode)->toBe('BB1')
        ->and($covered->verified)->toBeFalse()
        ->and($this->coverage->check('PR1')->status)->toBe(CoverageStatus::OutOfArea);

    Http::assertNothingSent();
});

it('falls back to the district check when postcodes.io is down', function () {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    $result = $this->coverage->check('BB2 4NY');

    expect($result->status)->toBe(CoverageStatus::Covered)
        ->and($result->verified)->toBeFalse();
});

it('ignores service areas that are switched off', function () {
    Http::fake();
    ServiceArea::factory()->districts(['PR1'])->inactive()->create();

    expect($this->coverage->check('PR1')->status)->toBe(CoverageStatus::OutOfArea);
});

it('refreshes cached lookups once they are older than the cache period', function () {
    PostcodeLookup::factory()->create(['fetched_at' => now()->subDays(40)]);
    Http::fake(['api.postcodes.io/postcodes/*' => Http::response(postcodesIoResult('BB2 4NY'))]);

    $this->coverage->check('BB2 4NY');

    Http::assertSentCount(1);
    expect(PostcodeLookup::query()->sole()->fetched_at->isToday())->toBeTrue();
});
