<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs we offer. "requires_diagnosis" repairs (water damage, data
 * recovery, diagnostics) are shown as "from" prices, confirmed after diagnosis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('short_name', 40)->nullable();
            $table->string('summary', 300)->nullable();
            $table->string('icon', 60)->nullable();
            $table->unsignedSmallInteger('typical_duration_minutes')->nullable();
            $table->boolean('requires_diagnosis')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_types');
    }
};
