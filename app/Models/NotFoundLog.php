<?php

namespace App\Models;

use Database\Factories\NotFoundLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An address that returned "page not found", with how often it was asked for.
 */
#[Fillable(['path', 'hits', 'last_referrer', 'last_user_agent', 'last_seen_at', 'redirect_id'])]
class NotFoundLog extends Model
{
    /** @use HasFactory<NotFoundLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Redirect, $this>
     */
    public function redirect(): BelongsTo
    {
        return $this->belongsTo(Redirect::class);
    }
}
