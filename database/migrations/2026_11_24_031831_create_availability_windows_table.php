<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The weekly collection timetable: time windows per weekday (ISO, 1 = Monday)
 * and how many collections fit in each.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_windows', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday');
            $table->string('label', 60);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('capacity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['weekday', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_windows');
    }
};
