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
