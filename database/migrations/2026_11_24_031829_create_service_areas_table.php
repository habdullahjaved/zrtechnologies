<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Towns we collect from. A postcode is covered when its district (outcode)
 * is listed here, or when it lies within radius_km of the area's centre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('town', 100);
            $table->string('county', 100)->default('Lancashire');
            $table->json('postcode_districts');
            $table->decimal('centre_lat', 9, 6)->nullable();
            $table->decimal('centre_lng', 9, 6)->nullable();
            $table->decimal('radius_km', 5, 1)->nullable();
            $table->unsignedInteger('collection_fee_pence')->default(0);
            $table->string('typical_collection_times', 200)->nullable();
            $table->string('summary', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_draft')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_areas');
    }
};
