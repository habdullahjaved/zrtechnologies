<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wording for customer emails, staff alerts and WhatsApp replies, editable in
 * the admin. Bodies use {placeholders}, e.g. {customer_first_name}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('channel', 20);
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->string('subject', 200)->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
