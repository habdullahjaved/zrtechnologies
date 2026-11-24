<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The repairs on a job, with the price and warranty agreed at the time
 * (copied from the price list, so later price changes don't alter old jobs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_job_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('part_quality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 160);
            $table->unsignedInteger('price_pence')->default(0);
            $table->unsignedSmallInteger('warranty_days')->default(0);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_job_items');
    }
};
