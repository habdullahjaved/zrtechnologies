<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local cache of postcodes.io answers (including "no such postcode"), so
 * repeat checks are instant and the free API is not called needlessly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postcode_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('postcode', 8)->unique();
            $table->string('outcode', 4)->index();
            $table->boolean('is_valid');
            $table->string('admin_district', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postcode_lookups');
    }
};
