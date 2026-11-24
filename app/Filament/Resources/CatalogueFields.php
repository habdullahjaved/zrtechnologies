<?php

namespace App\Filament\Resources;

use App\Models\DeviceModel;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\RepairType;
use App\Models\Service;
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
     * Records whose slugs share one web address space, so a slug must be unique
     * across them: /iphone-repair/{slug} serves repair pages and device models
     * (and repair types are kept distinct from models), /blog/{slug} serves
     * categories and posts.
     *
     * @var array<class-string<Model>, array<class-string<Model>, string>>
     */
    private const array SHARED_URL_SPACES = [
        DeviceModel::class => [Service::class => 'A repair page', RepairType::class => 'A repair type'],
        Service::class => [DeviceModel::class => 'A device model'],
        RepairType::class => [DeviceModel::class => 'A device model'],
        Post::class => [PostCategory::class => 'A blog category'],
        PostCategory::class => [Post::class => 'A blog post'],
    ];

    /**
     * A URL slug, unique in its table and across any table sharing its web addresses.
     *
     * @param  class-string<Model>  $model
     */
    public static function slug(string $model, string $helperText = 'Used in the web address. Lowercase letters, numbers and hyphens only.'): TextInput
    {
        $others = self::SHARED_URL_SPACES[$model] ?? [];

        return TextInput::make('slug')
            ->label('URL slug')
            ->helperText($helperText)
            ->required()
            ->maxLength(120)
            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->validationMessages(['regex' => 'Use lowercase letters, numbers and single hyphens only, e.g. iphone-15-pro.'])
            ->unique(ignoreRecord: true)
            ->rules($others === [] ? [] : [
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($others): void {
                    foreach ($others as $other => $label) {
                        if ($other::query()->where('slug', $value)->exists()) {
                            $fail("{$label} already uses this address. Choose a different slug.");

                            return;
                        }
                    }
                },
            ]);
    }
}
