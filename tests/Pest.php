<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelSettings\Settings;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against the MySQL testing database (see phpunit.xml) so
| behaviour matches production. Each test runs inside a refreshed database.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Re-read a settings class from the database, bypassing the instance the
 * container has already resolved during the test.
 *
 * @template TSettings of \Spatie\LaravelSettings\Settings
 *
 * @param  class-string<TSettings>  $class
 * @return TSettings
 */
function freshSettings(string $class): Settings
{
    app()->forgetInstance($class);

    return app($class);
}

/**
 * A postcodes.io success response.
 *
 * @return array<string, mixed>
 */
function postcodesIoResult(string $postcode, float $latitude = 53.7449, float $longitude = -2.4891): array
{
    return ['status' => 200, 'result' => [
        'postcode' => $postcode,
        'outcode' => explode(' ', $postcode)[0],
        'admin_district' => 'Blackburn with Darwen',
        'admin_ward' => 'Audley & Queen’s Park',
        'latitude' => $latitude,
        'longitude' => $longitude,
        'country' => 'England',
        'codes' => ['admin_district' => 'E06000008'],
    ]];
}
