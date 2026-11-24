<?php

namespace App\Models;

use Database\Factories\SeoMetaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * SEO overrides for one record. Empty fields fall back to title patterns and
 * site defaults.
 *
 * @property array<int|string, mixed>|null $schema_override
 */
#[Table('seo_meta')]
#[Fillable(['title', 'description', 'focus_keyword', 'canonical_url', 'noindex', 'nofollow', 'og_image', 'og_image_alt', 'schema_override'])]
class SeoMeta extends Model
{
    /** @use HasFactory<SeoMetaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'noindex' => 'boolean',
            'nofollow' => 'boolean',
            'schema_override' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
