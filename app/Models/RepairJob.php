<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Support\Money;
use Database\Factories\RepairJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A repair in the workshop. IMEI and serial number are encrypted at rest;
 * a keyed hash of the IMEI allows exact-match search.
 *
 * @property string $reference
 * @property JobStatus $status
 * @property string|null $imei
 * @property string|null $serial_number
 * @property Carbon|null $collection_date
 * @property int $quoted_price_pence
 * @property int $collection_fee_pence
 * @property int|null $final_price_pence
 * @property Carbon|null $booked_at
 * @property Carbon|null $collected_at
 * @property Carbon|null $diagnosed_at
 * @property Carbon|null $ready_at
 * @property Carbon|null $returned_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $review_requested_at
 * @property Carbon|null $warranty_expires_at
 */
#[Fillable([
    'reference', 'status', 'lead_id', 'customer_id', 'technician_id', 'service_area_id',
    'device_model_id', 'device_colour', 'device_storage', 'imei', 'serial_number',
    'reported_fault', 'diagnosis', 'customer_notes',
    'collection_address_id', 'return_address_id', 'collection_date', 'availability_window_id', 'collection_window',
    'quoted_price_pence', 'collection_fee_pence', 'final_price_pence',
    'terms_accepted_at', 'passcode_policy_accepted_at', 'start_within_cancellation_period',
    'booked_at', 'collected_at', 'diagnosed_at', 'ready_at', 'returned_at', 'completed_at', 'cancelled_at', 'paid_at',
    'warranty_expires_at',
])]
#[Hidden(['imei', 'imei_hash', 'serial_number'])]
class RepairJob extends Model
{
    /** @use HasFactory<RepairJobFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'booked',
        'quoted_price_pence' => 0,
        'collection_fee_pence' => 0,
        'start_within_cancellation_period' => false,
    ];

    protected static function booted(): void
    {
        static::saving(function (RepairJob $job): void {
            if ($job->isDirty('imei')) {
                $job->imei_hash = filled($job->imei) ? self::hashImei($job->imei) : null;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'imei' => 'encrypted',
            'serial_number' => 'encrypted',
            'collection_date' => 'date',
            'quoted_price_pence' => 'integer',
            'collection_fee_pence' => 'integer',
            'final_price_pence' => 'integer',
            'start_within_cancellation_period' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'passcode_policy_accepted_at' => 'datetime',
            'booked_at' => 'datetime',
            'collected_at' => 'datetime',
            'diagnosed_at' => 'datetime',
            'ready_at' => 'datetime',
            'returned_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
            'review_requested_at' => 'datetime',
            'warranty_expires_at' => 'date',
        ];
    }

    /**
     * Keyed hash of an IMEI (digits only), so staff can search without the IMEI being stored in plain text.
     */
    public static function hashImei(string $imei): string
    {
        return hash_hmac('sha256', preg_replace('/\D/', '', $imei) ?? '', (string) config('app.key'));
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
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
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * @return BelongsTo<ServiceArea, $this>
     */
    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class);
    }

    /**
     * @return BelongsTo<DeviceModel, $this>
     */
    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function collectionAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'collection_address_id');
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function returnAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'return_address_id');
    }

    /**
     * @return BelongsTo<AvailabilityWindow, $this>
     */
    public function availabilityWindow(): BelongsTo
    {
        return $this->belongsTo(AvailabilityWindow::class);
    }

    /**
     * @return HasMany<RepairJobItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RepairJobItem::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * @return HasMany<JobStatusChange, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(JobStatusChange::class)->latest('created_at')->latest('id');
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    /**
     * Jobs still in the workshop pipeline.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', JobStatus::active());
    }

    /**
     * Repairs plus the collection fee: what the customer expects to pay.
     */
    public function estimatedTotalPence(): int
    {
        return $this->quoted_price_pence + $this->collection_fee_pence;
    }

    public function estimatedTotalLabel(): string
    {
        return Money::format($this->estimatedTotalPence());
    }

    /**
     * e.g. "Tuesday 1 December 2026, 9am–12pm".
     */
    public function collectionSlotLabel(): ?string
    {
        if ($this->collection_date === null) {
            return null;
        }

        return $this->collection_date->format('l j F Y').($this->collection_window ? ', '.$this->collection_window : '');
    }

    /**
     * Where the phone goes back to: the return address, or the collection address.
     */
    public function deliveryAddress(): ?Address
    {
        return $this->returnAddress ?? $this->collectionAddress;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'technician_id', 'device_model_id', 'reported_fault', 'diagnosis',
                'collection_date', 'availability_window_id', 'quoted_price_pence', 'collection_fee_pence', 'final_price_pence', 'paid_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('workshop');
    }
}
