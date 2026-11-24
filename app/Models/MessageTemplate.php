<?php

namespace App\Models;

use App\Enums\MessageChannel;
use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Editable wording for an email, team alert or WhatsApp message.
 *
 * The key identifies where it is used (e.g. "booking.confirmation") and never
 * changes; the subject and body may use {placeholders}.
 *
 * @property string $key
 * @property MessageChannel $channel
 * @property string|null $subject
 * @property string $body
 * @property bool $is_active
 */
#[Fillable(['key', 'channel', 'name', 'description', 'subject', 'body', 'is_active'])]
class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => MessageChannel::class,
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['subject', 'body', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('templates');
    }
}
