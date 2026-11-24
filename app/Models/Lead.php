<?php

namespace App\Models;

use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Support\Money;
use App\Support\Phone;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * An enquiry or booking, with the attribution captured when it arrived.
 *
 * @property string $reference
 * @property LeadType $type
 * @property LeadStatus $status
 * @property Channel $channel
 * @property Channel|null $first_touch_channel
 * @property bool $is_partial
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property array<string, mixed>|null $details
 * @property array<string, mixed>|null $attribution
 * @property int|null $estimated_value_pence
 * @property int|null $final_value_pence
 * @property Carbon|null $follow_up_at
 * @property Carbon|null $contacted_at
 * @property Carbon|null $converted_at
 * @property string $event_id
 */
#[Fillable([
    'reference', 'type', 'status', 'is_partial', 'service_category_id', 'customer_id', 'assigned_to',
    'name', 'email', 'phone', 'postcode', 'company_name', 'device_model_id', 'repair_type_id', 'service_area_id',
    'message', 'details', 'estimated_value_pence', 'final_value_pence', 'lost_reason', 'follow_up_at',
    'contacted_at', 'converted_at', 'marketing_consent',
    'channel', 'first_touch_channel', 'gclid', 'gbraid', 'wbraid', 'fbclid', 'fbc', 'fbp', 'msclkid', 'ttclid',
    'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing_page', 'referrer',
    'device_type', 'ga_client_id', 'attribution', 'event_id', 'ip_hash', 'user_agent',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
        'is_partial' => false,
        'marketing_consent' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LeadType::class,
            'status' => LeadStatus::class,
            'channel' => Channel::class,
            'first_touch_channel' => Channel::class,
            'is_partial' => 'boolean',
            'marketing_consent' => 'boolean',
            'details' => 'array',
            'attribution' => 'array',
            'estimated_value_pence' => 'integer',
            'final_value_pence' => 'integer',
            'follow_up_at' => 'datetime',
            'contacted_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsTo<DeviceModel, $this>
     */
    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    /**
     * @return BelongsTo<RepairType, $this>
     */
    public function repairType(): BelongsTo
    {
        return $this->belongsTo(RepairType::class);
    }

    /**
     * @return BelongsTo<ServiceArea, $this>
     */
    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class);
    }

    /**
     * @return HasOne<RepairJob, $this>
     */
    public function repairJob(): HasOne
    {
        return $this->hasOne(RepairJob::class)->latestOfMany();
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', LeadStatus::open());
    }

    /**
     * Open leads whose follow-up time has passed.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function followUpDue(Builder $query): void
    {
        $query->whereIn('status', LeadStatus::open())->where('follow_up_at', '<=', now());
    }

    /**
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

    public function displayName(): string
    {
        return $this->name ?: ($this->email ?: ($this->phone ? Phone::display($this->phone) : $this->reference));
    }

    public function firstName(): string
    {
        return str($this->name ?? '')->trim()->before(' ')->toString() ?: 'there';
    }

    public function estimatedValueLabel(): ?string
    {
        return $this->estimated_value_pence !== null ? Money::format($this->estimated_value_pence) : null;
    }

    /**
     * A value from the attribution snapshot, e.g. attributionValue('first_touch.utm_source').
     */
    public function attributionValue(string $key): mixed
    {
        return data_get($this->attribution, $key);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'type', 'assigned_to', 'customer_id', 'name', 'email', 'phone', 'estimated_value_pence', 'final_value_pence', 'lost_reason', 'follow_up_at', 'is_partial'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('crm');
    }
}
