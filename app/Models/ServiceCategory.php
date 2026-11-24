<?php

namespace App\Models;

use App\Enums\FormType;
use Database\Factories\ServiceCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A line of business, e.g. iPhone Repair. Later: accessories, printing, web development.
 *
 * @property FormType $form_type
 */
#[Fillable(['name', 'slug', 'description', 'form_type', 'is_active', 'sort'])]
class ServiceCategory extends Model
{
    /** @use HasFactory<ServiceCategoryFactory> */
    use HasFactory;

    public const string IPHONE_REPAIR = 'iphone-repair';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'form_type' => FormType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<RepairType, $this>
     */
    public function repairTypes(): HasMany
    {
        return $this->hasMany(RepairType::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
