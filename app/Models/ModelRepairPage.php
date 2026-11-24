<?php

namespace App\Models;

use App\Services\Seo\ContentQuality;
use App\Settings\SiteSettings;
use Database\Factories\ModelRepairPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An optional page for one repair on one model, e.g. iPhone 15 Pro screen
 * replacement. Indexable only once its own content passes the checks.
 *
 * @property list<array{type: string, data: array<string, mixed>}>|null $blocks
 * @property string $similarity_score
 */
#[Fillable(['device_model_id', 'repair_type_id', 'intro', 'blocks', 'is_enabled'])]
class ModelRepairPage extends Model
{
    /** @use HasFactory<ModelRepairPageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_enabled' => 'boolean',
            'unique_word_count' => 'integer',
            'similarity_score' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(ContentQuality::class)->refreshModelRepairPages());
    }

    /**
     * @return BelongsTo<DeviceModel, $this>
     */
    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    /**
     * @return BelongsTo<RepairType, $this>
     */
    public function repairType(): BelongsTo
    {
        return $this->belongsTo(RepairType::class);
    }

    public function passesContentChecks(): bool
    {
        return app(ContentQuality::class)->passes(
            $this->unique_word_count,
            (float) $this->similarity_score,
            app(SiteSettings::class)->min_unique_words_model_repair,
        );
    }
}
