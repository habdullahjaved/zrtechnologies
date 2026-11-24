<?php

namespace App\Models;

use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Sends visitors from an old address to a new one (301 permanent or 302 temporary).
 */
#[Fillable(['from_path', 'to_path', 'status_code', 'is_active', 'is_automatic'])]
class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
            'is_automatic' => 'boolean',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $redirect): void {
            $redirect->from_path = self::normalisePath($redirect->from_path);

            if (! Str::startsWith($redirect->to_path, ['http://', 'https://'])) {
                $redirect->to_path = self::normalisePath($redirect->to_path);
            }
        });
    }

    /**
     * A path in the form we store and match: leading slash, no trailing slash,
     * no domain, no query string, lowercase.
     */
    public static function normalisePath(string $path): string
    {
        $path = (string) (parse_url(trim($path), PHP_URL_PATH) ?? '');
        $path = '/'.trim(rawurldecode($path), '/');

        return mb_strtolower($path);
    }

    /**
     * Where to send the visitor: an absolute URL or a path on this site.
     */
    public function targetUrl(): string
    {
        return Str::startsWith($this->to_path, ['http://', 'https://']) ? $this->to_path : url($this->to_path);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['from_path', 'to_path', 'status_code', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('seo');
    }
}
