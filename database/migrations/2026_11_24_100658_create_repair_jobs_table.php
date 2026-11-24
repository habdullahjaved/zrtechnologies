<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A repair in the workshop, from booking to return. The reference
 * (e.g. ZR-2026-000123) is what the customer sees and tracks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->string('status', 30)->default('booked')->index();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_area_id')->nullable()->constrained()->nullOnDelete();

            // Device.
            $table->foreignId('device_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_colour', 40)->nullable();
            $table->string('device_storage', 20)->nullable();
            $table->text('imei')->nullable()->comment('Encrypted');
            $table->string('imei_hash', 64)->nullable()->index()->comment('Keyed hash for exact-match search');
            $table->text('serial_number')->nullable()->comment('Encrypted');
            $table->text('reported_fault')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('customer_notes')->nullable();

            // Collection and return.
            $table->foreignId('collection_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('return_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->date('collection_date')->nullable();
            $table->foreignId('availability_window_id')->nullable()->constrained()->nullOnDelete();
            $table->string('collection_window', 40)->nullable()->comment('Snapshot, e.g. 9am–12pm');

            // Money (pence).
            $table->unsignedInteger('quoted_price_pence')->default(0);
            $table->unsignedInteger('collection_fee_pence')->default(0);
            $table->unsignedInteger('final_price_pence')->nullable();

            // Consents captured at booking.
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('passcode_policy_accepted_at')->nullable();
            $table->boolean('start_within_cancellation_period')->default(false);

            // Milestones.
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('diagnosed_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('warranty_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['collection_date', 'availability_window_id', 'status'], 'repair_jobs_collection_slot_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_jobs');
    }
};
