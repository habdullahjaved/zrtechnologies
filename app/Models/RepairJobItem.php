<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\RepairJobItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One repair on a job, with the price and warranty agreed at booking.
 *
 * @property string $description
 * @property int $price_pence
 * @property int $warranty_days
 */
#[Fillable(['repair_job_id', 'repair_type_id', 'part_quality_id', 'description', 'price_pence', 'warranty_days', 'sort'])]
class RepairJobItem extends Model
{
    /** @use HasFactory<RepairJobItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_pence' => 'integer',
            'warranty_days' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RepairJob, $this>
     */
    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }

    /**
     * @return BelongsTo<RepairType, $this>
     */
    public function repairType(): BelongsTo
    {
        return $this->belongsTo(RepairType::class);
    }

    /**
     * @return BelongsTo<PartQuality, $this>
     */
    public function partQuality(): BelongsTo
    {
        return $this->belongsTo(PartQuality::class);
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price_pence);
    }
}
