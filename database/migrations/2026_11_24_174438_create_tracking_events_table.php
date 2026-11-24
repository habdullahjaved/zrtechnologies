<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every server-side conversion sent (or deliberately not sent) to Google
 * Analytics, Meta and Google Ads, with the response, for the Tracking
 * Health page. Personal data in payloads is stored hashed only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('repair_job_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_id', 64)->index();
            $table->string('event_name', 60);
            $table->string('platform', 20);
            $table->string('kind', 20);
            $table->string('status', 30);
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->string('error', 500)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->boolean('is_test')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'status', 'created_at']);
            $table->unique(['event_id', 'platform', 'event_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
