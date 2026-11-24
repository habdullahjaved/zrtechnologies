<?php

namespace App\Filament\Resources;

use App\Models\DeviceModel;
use App\Models\RepairType;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Form fields shared by catalogue resources.
 */
final class CatalogueFields
{
    /**
     * A name field that fills in the slug while a new record is being created.
     */
    public static function name(string $slugField = 'slug'): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->maxLength(100)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state, string $operation) use ($slugField): void {
                if ($operation !== 'create' || ($get($slugField) ?? '') !== Str::slug((string) $old)) {
                    return;
                }

                $set($slugField, Str::slug((string) $state));
            });
    }

    /**
     * A URL slug. Repair types and device models share the /iphone-repair/{slug}
     * URL space, so a slug must be unique across both tables.
     *
     * @param  class-string<Model>  $model
     */
    public static function slug(string $model, string $helperText = 'Used in the web address. Lowercase letters, numbers and hyphens only.'): TextInput
    {
        $sharesRepairUrls = in_array($model, [DeviceModel::class, RepairType::class], true);

        return TextInput::make('slug')
            ->label('URL slug')
            ->helperText($helperText)
            ->required()
            ->maxLength(120)
            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->validationMessages(['regex' => 'Use lowercase letters, numbers and single hyphens only, e.g. iphone-15-pro.'])
            ->unique(ignoreRecord: true)
            ->rules($sharesRepairUrls ? [
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($model): void {
                    $other = $model === DeviceModel::class ? RepairType::class : DeviceModel::class;

                    if ($other::query()->where('slug', $value)->exists()) {
                        $fail($other === RepairType::class
                            ? 'A repair type already uses this slug. Choose a different one.'
                            : 'A device model already uses this slug. Choose a different one.');
                    }
                },
            ] : []);
    }
}
