<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-record SEO overrides (pages, service pages, models, locations, posts,
 * categories, videos). Anything left empty falls back to the title patterns
 * and defaults in Website & SEO settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('title', 120)->nullable();
            $table->string('description', 320)->nullable();
            $table->string('focus_keyword', 120)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('noindex')->default(false);
            $table->boolean('nofollow')->default(false);
            $table->string('og_image', 500)->nullable();
            $table->string('og_image_alt', 200)->nullable();
            $table->json('schema_override')->nullable();
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_meta');
    }
};
