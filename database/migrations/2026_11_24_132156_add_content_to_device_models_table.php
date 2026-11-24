<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Model pages (/iphone-repair/{model}): an introduction and content blocks on
 * top of the model notes and the price list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_models', function (Blueprint $table) {
            $table->text('intro')->nullable()->after('notes');
            $table->json('blocks')->nullable()->after('intro');
        });
    }

    public function down(): void
    {
        Schema::table('device_models', function (Blueprint $table) {
            $table->dropColumn(['intro', 'blocks']);
        });
    }
};
