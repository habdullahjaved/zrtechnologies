<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service pages, e.g. /iphone-repair/screen-replacement. Each belongs to a
 * service category and covers one or more repair types (the camera page
 * covers rear camera, front camera and camera lens). A repair service page is
 * only shown while at least one of its repair types is offered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('heading', 160)->nullable();
            $table->string('summary', 300)->nullable();
            $table->text('intro')->nullable();
            $table->json('blocks')->nullable();
            $table->string('icon', 40)->nullable();
            $table->string('og_image', 200)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamps();

            $table->index(['service_category_id', 'is_active', 'sort']);
        });

        Schema::create('repair_type_service', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);

            $table->primary(['service_id', 'repair_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_type_service');
        Schema::dropIfExists('services');
    }
};
