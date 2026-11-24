<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Location pages (/iphone-repair-{town}). Their local content is checked for
 * length and for similarity with the other towns; a page stays noindex until
 * it passes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->text('intro')->nullable()->after('summary');
            $table->json('blocks')->nullable()->after('intro');
            $table->text('landmarks')->nullable()->after('blocks');
            $table->unsignedInteger('unique_word_count')->default(0)->after('landmarks');
            $table->decimal('similarity_score', 5, 2)->default(0)->after('unique_word_count');
        });
    }

    public function down(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropColumn(['intro', 'blocks', 'landmarks', 'unique_word_count', 'similarity_score']);
        });
    }
};
