<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Phase 4: title patterns, robots.txt additions, the unique-content thresholds
 * for location and model × repair pages, and the Google review link.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.title_pattern_service', '{name} in Blackburn | From {price}');
        $this->migrator->add('site.title_pattern_model', '{model} Repair in Blackburn | From {price}');
        $this->migrator->add('site.title_pattern_model_repair', '{repair} for {model} in Blackburn | From {price}');
        $this->migrator->add('site.title_pattern_location', 'iPhone Repair {town} | Collection & Delivery');
        $this->migrator->add('site.title_pattern_post', '{title}');
        $this->migrator->add('site.title_pattern_post_category', '{name} | iPhone Guides');
        $this->migrator->add('site.robots_extra', null);
        $this->migrator->add('site.index_tag_pages', false);
        $this->migrator->add('site.min_unique_words_location', 400);
        $this->migrator->add('site.min_unique_words_model_repair', 300);
        $this->migrator->add('site.max_similarity_percent', 50);
        $this->migrator->add('site.show_coming_soon', true);

        $this->migrator->add('social.google_review_url', null);
    }
};
