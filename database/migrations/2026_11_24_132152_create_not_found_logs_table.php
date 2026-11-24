<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addresses visitors asked for that do not exist, so they can be redirected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->unique();
            $table->unsignedInteger('hits')->default(1);
            $table->string('last_referrer', 500)->nullable();
            $table->string('last_user_agent', 300)->nullable();
            $table->timestamp('last_seen_at');
            $table->foreignId('redirect_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
    }
};
