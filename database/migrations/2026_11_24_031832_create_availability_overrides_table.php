<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-off capacity changes for a window on a given date (0 closes it).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_overrides', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('availability_window_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('capacity');
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->unique(['date', 'availability_window_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_overrides');
    }
};
