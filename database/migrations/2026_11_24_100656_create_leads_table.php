<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every enquiry and booking, with where the visitor came from (attribution)
 * captured at the moment they got in touch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->string('type', 30);
            $table->string('status', 20)->default('new');
            $table->boolean('is_partial')->default(false)->comment('An online booking started but not finished');
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // What the visitor told us.
            $table->string('name', 160)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->string('postcode', 10)->nullable();
            $table->string('company_name', 120)->nullable();
            $table->foreignId('device_model_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('repair_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_area_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->json('details')->nullable()->comment('Form answers, e.g. the booking choices or callback time');

            // Pipeline.
            $table->unsignedInteger('estimated_value_pence')->nullable();
            $table->unsignedInteger('final_value_pence')->nullable();
            $table->string('lost_reason', 255)->nullable();
            $table->timestamp('follow_up_at')->nullable()->index();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->boolean('marketing_consent')->default(false);

            // Attribution (last non-direct touch; first touch in the snapshot).
            $table->string('channel', 30)->index();
            $table->string('first_touch_channel', 30)->nullable();
            $table->string('gclid', 255)->nullable()->index();
            $table->string('gbraid', 255)->nullable();
            $table->string('wbraid', 255)->nullable();
            $table->string('fbclid', 255)->nullable()->index();
            $table->string('fbc', 255)->nullable();
            $table->string('fbp', 255)->nullable();
            $table->string('msclkid', 255)->nullable();
            $table->string('ttclid', 255)->nullable();
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 160)->nullable();
            $table->string('utm_term', 160)->nullable();
            $table->string('utm_content', 160)->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('device_type', 10)->nullable();
            $table->string('ga_client_id', 60)->nullable();
            $table->json('attribution')->nullable()->comment('First and last touch plus pages viewed');

            // Conversion tracking and audit.
            $table->uuid('event_id')->unique();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['channel', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
