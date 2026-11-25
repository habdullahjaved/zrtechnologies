<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Site verification codes (Google, Bing, Pinterest, Meta) and the Pinterest
 * Tag, all managed in the admin. Also switches indexing on: the site is live.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.google_site_verification', null);
        $this->migrator->add('site.bing_site_verification', null);
        $this->migrator->add('site.pinterest_site_verification', null);
        $this->migrator->add('site.facebook_domain_verification', null);

        $this->migrator->add('tracking.pinterest_tag_id', null);

        $this->migrator->update('site.allow_indexing', fn (): bool => true);
    }

    public function down(): void
    {
        $this->migrator->delete('site.google_site_verification');
        $this->migrator->delete('site.bing_site_verification');
        $this->migrator->delete('site.pinterest_site_verification');
        $this->migrator->delete('site.facebook_domain_verification');
        $this->migrator->delete('tracking.pinterest_tag_id');
    }
};
