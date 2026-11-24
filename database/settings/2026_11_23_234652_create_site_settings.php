<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Site-wide SEO defaults. Indexing starts switched off; it is turned on at launch
 * (and is only ever honoured in production).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.site_name', 'ZR Technologies');
        $this->migrator->add('site.title_separator', '|');
        $this->migrator->add('site.default_meta_title', 'iPhone Repair Blackburn | We Collect, Repair & Return');
        $this->migrator->add('site.default_meta_description', 'iPhone repair in Blackburn with collection and delivery to your door. Screens, batteries, charging ports and more, with quality parts and a warranty.');
        $this->migrator->add('site.default_og_image', 'images/og/og-default.jpg');
        $this->migrator->add('site.allow_indexing', false);
        $this->migrator->add('site.announcement', null);
        $this->migrator->add('site.trademark_disclaimer', 'ZR Technologies is an independent repair provider and is not affiliated with or endorsed by Apple Inc. iPhone is a trademark of Apple Inc., registered in the U.S. and other countries.');
    }
};
