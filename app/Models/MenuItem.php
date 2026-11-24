<?php

namespace App\Models;

use App\Enums\MenuLocation;
use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A link in one of the editable menus.
 *
 * @property MenuLocation $menu
 */
#[Fillable(['menu', 'label', 'url', 'opens_in_new_tab', 'is_active', 'sort'])]
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'menu' => MenuLocation::class,
            'opens_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forMenu(Builder $query, MenuLocation $menu): void
    {
        $query->where('menu', $menu->value)->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    public function href(): string
    {
        return Str::startsWith($this->url, ['http://', 'https://', 'mailto:', 'tel:']) ? $this->url : url($this->url);
    }

    public function isExternal(): bool
    {
        return Str::startsWith($this->url, ['http://', 'https://']) && ! Str::startsWith($this->url, url('/'));
    }
}
