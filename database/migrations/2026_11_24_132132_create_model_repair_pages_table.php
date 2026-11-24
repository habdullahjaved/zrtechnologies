<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional deeper pages, e.g. /iphone-repair/iphone-15-pro/screen-replacement.
 * They only exist when switched on, and stay noindex (canonical to the model
 * page) until their own content passes the unique-content checks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_repair_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_type_id')->constrained()->cascadeOnDelete();
            $table->text('intro')->nullable();
            $table->json('blocks')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedInteger('unique_word_count')->default(0);
            $table->decimal('similarity_score', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['device_model_id', 'repair_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_repair_pages');
    }
};
