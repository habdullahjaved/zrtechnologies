<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website pages. Rows with a "key" are system pages (home, how it works, legal
 * pages...): their address is fixed and they cannot be deleted, but their
 * heading, introduction, content blocks and SEO are edited in the admin.
 * Rows without a key are custom pages served at /{slug}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->nullable()->unique();
            $table->string('title', 160);
            $table->string('slug', 160)->unique();
            $table->string('eyebrow', 80)->nullable();
            $table->text('intro')->nullable();
            $table->json('blocks')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('needs_legal_review')->default(false);
            $table->boolean('show_in_sitemap')->default(true);
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
