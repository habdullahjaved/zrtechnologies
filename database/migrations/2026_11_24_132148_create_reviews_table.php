<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Genuine customer reviews only (DMCC Act 2024). Sample reviews exist only in
 * local development and are never shown in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);
            $table->string('reviewer_name', 120);
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->date('reviewed_at');
            $table->string('source_url', 500)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('repair_job_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_area_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_sample')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
