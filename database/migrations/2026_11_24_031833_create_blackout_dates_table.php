<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whole days with no collections (bank holidays, holidays, training).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blackout_dates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('reason', 200);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blackout_dates');
    }
};
