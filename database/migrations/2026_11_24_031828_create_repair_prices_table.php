<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The price matrix: device model × repair type × part quality.
 *
 * part_quality_id is nullable (most repairs come in one quality) and unique
 * indexes treat NULLs as distinct, so uniqueness is enforced on a stored
 * generated column that maps "no quality" to 0. Works on MySQL 8 and MariaDB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_quality_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('part_quality_key')->storedAs('coalesce(`part_quality_id`, 0)');
            $table->unsignedInteger('price_pence');
            $table->unsignedInteger('sale_price_pence')->nullable();
            $table->unsignedSmallInteger('warranty_days')->default(90);
            $table->boolean('in_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_draft')->default(true);
            $table->timestamps();

            $table->unique(['device_model_id', 'repair_type_id', 'part_quality_key'], 'repair_prices_model_repair_quality_unique');
            $table->index(['repair_type_id', 'is_active']);
            $table->index('is_draft');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_prices');
    }
};
