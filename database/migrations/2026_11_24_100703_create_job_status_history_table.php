<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every status change on a repair job: who made it, an internal note, the
 * message shown to the customer and whether they were notified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('note')->nullable()->comment('Internal only');
            $table->text('customer_message')->nullable()->comment('Shown on the tracking page and in the email');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('customer_notified')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['repair_job_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_status_history');
    }
};
