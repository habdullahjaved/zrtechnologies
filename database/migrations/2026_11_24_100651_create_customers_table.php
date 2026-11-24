<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * People who have booked or enquired. Matched by mobile or email so one
 * person has one record however many times they get in touch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 80);
            $table->string('last_name', 80)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 20)->nullable()->index()->comment('E.164, e.g. +447360127538');
            $table->string('phone_alt', 20)->nullable();
            $table->string('company_name', 120)->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('marketing_consent_at')->nullable();
            $table->string('marketing_consent_source', 60)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
