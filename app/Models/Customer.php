<?php

namespace App\Models;

use App\Support\Phone;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Someone who has booked or enquired. Phones are stored in E.164.
 *
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property bool $marketing_consent
 * @property Carbon|null $marketing_consent_at
 * @property-read string $full_name
 */
#[Fillable(['first_name', 'last_name', 'email', 'phone', 'phone_alt', 'company_name', 'marketing_consent', 'marketing_consent_at', 'marketing_consent_source', 'notes'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marketing_consent' => 'boolean',
            'marketing_consent_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * @return HasOne<Address, $this>
     */
    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->ofMany(['is_default' => 'max', 'id' => 'max']);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<RepairJob, $this>
     */
    public function repairJobs(): HasMany
    {
        return $this->hasMany(RepairJob::class);
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * Normalise to E.164 so the same number always matches.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => blank($value) ? null : Phone::toE164($value));
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => blank($value) ? null : mb_strtolower(trim($value)));
    }

    /**
     * Record opt-in to marketing with when and where it was given. Opting out
     * is recorded the same way, so the latest choice always wins.
     */
    public function recordMarketingConsent(bool $consented, string $source): void
    {
        if ($consented === $this->marketing_consent && $this->exists) {
            return;
        }

        $this->forceFill([
            'marketing_consent' => $consented,
            'marketing_consent_at' => now(),
            'marketing_consent_source' => $source,
        ]);
    }

    /**
     * The mail notification route; customers without an email are skipped.
     */
    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'email', 'phone', 'phone_alt', 'company_name', 'marketing_consent'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('crm');
    }
}
