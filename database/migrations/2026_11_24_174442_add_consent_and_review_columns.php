<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leads remember the visitor's cookie choice at the moment they enquired, so
 * later server-side sends (including the offline "repair completed"
 * conversion) respect it. Jobs remember when a review was requested.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->json('consent')->nullable()->after('attribution')->comment('Cookie choice when the lead was captured');
            $table->string('ga_session_id', 40)->nullable()->after('ga_client_id');
        });

        Schema::table('repair_jobs', function (Blueprint $table) {
            $table->timestamp('review_requested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['consent', 'ga_session_id']);
        });

        Schema::table('repair_jobs', function (Blueprint $table) {
            $table->dropColumn('review_requested_at');
        });
    }
};
