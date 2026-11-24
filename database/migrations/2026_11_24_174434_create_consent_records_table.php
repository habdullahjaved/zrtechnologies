<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proof of each cookie choice a visitor made (UK GDPR / PECR): what they chose,
 * when, under which policy version. No IP address is stored, only a keyed hash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('consent_id')->index();
            $table->string('action', 20);
            $table->boolean('analytics')->default(false);
            $table->boolean('marketing')->default(false);
            $table->string('policy_version', 20);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('page', 500)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
