<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Running numbers for references such as ZR-2026-000123, one row per
 * sequence and year. Rows are locked while a number is taken so two bookings
 * at the same moment never share a reference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['name', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_sequences');
    }
};
