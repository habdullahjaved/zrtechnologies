<?php

namespace App\Models;

use Database\Factories\ConsentRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One cookie choice made by a visitor (accept all, reject all or custom).
 *
 * @property Carbon $created_at
 */
#[Fillable(['consent_id', 'action', 'analytics', 'marketing', 'policy_version', 'ip_hash', 'user_agent', 'page'])]
class ConsentRecord extends Model
{
    /** @use HasFactory<ConsentRecordFactory> */
    use HasFactory;

    public const string ACCEPT_ALL = 'accept_all';

    public const string REJECT_ALL = 'reject_all';

    public const string CUSTOM = 'custom';

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'analytics' => 'boolean',
            'marketing' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
