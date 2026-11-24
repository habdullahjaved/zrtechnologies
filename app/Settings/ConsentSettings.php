<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Cookie banner wording. Changing the policy version asks every visitor to
 * choose again.
 */
class ConsentSettings extends Settings
{
    public string $policy_version;

    public string $banner_title;

    public string $banner_text;

    public string $analytics_description;

    public string $marketing_description;

    public static function group(): string
    {
        return 'consent';
    }
}
