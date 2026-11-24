<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frequently asked questions. They appear on /faqs (grouped by topic) and can
 * be attached to any page, service page, model, location or post.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 30);
            $table->string('question', 255);
            $table->text('answer');
            $table->boolean('show_on_faq_page')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'topic', 'sort']);
        });

        Schema::create('faqables', function (Blueprint $table) {
            $table->foreignId('faq_id')->constrained()->cascadeOnDelete();
            $table->morphs('faqable');
            $table->unsignedSmallInteger('sort')->default(0);

            $table->primary(['faq_id', 'faqable_type', 'faqable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqables');
        Schema::dropIfExists('faqs');
    }
};
