<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short videos (uploaded, YouTube Shorts, TikTok or Instagram Reels). Until a
 * real video is added, a branded placeholder card ("Video coming soon") is shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 160)->unique();
            $table->string('description', 1000)->nullable();
            $table->string('source', 20)->default('youtube');
            $table->string('external_url', 500)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('poster_path', 500)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('transcript')->nullable();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_placeholder')->default(true);
            $table->string('placeholder_label', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
