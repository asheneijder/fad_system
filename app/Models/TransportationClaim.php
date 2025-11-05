<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;

class TransportationClaim extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    protected $table = 'transportation_claims';

    protected $fillable = [
        'user_id',
        'claim_date',
        'transport_type',
        'purpose',
        'from_location',
        'to_location',
        'distance_km',
        'rate_per_km',
        'amount',
        'currency',
        'trip_type',
        'number_of_trips',
        'receipt_number',
        'remarks',
        'status',
        'approver_id',
        'approval_date',
        'rejection_reason',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'approval_date' => 'datetime',
        'distance_km' => 'decimal:2',
        'rate_per_km' => 'decimal:2',
        'amount' => 'decimal:2',
        'number_of_trips' => 'integer',
    ];

    // Transportation type constants
    const TYPE_GRAB = 'grab';

    const TYPE_TAXI = 'taxi';

    const TYPE_MRT = 'mrt';

    const TYPE_LRT = 'lrt';

    const TYPE_ECL = 'ecl'; // Electric Train

    const TYPE_BUS = 'bus';

    const TYPE_TOLL = 'toll';

    const TYPE_PARKING = 'parking';

    const TYPE_OTHER = 'other';

    // Trip type constants
    const TRIP_ONE_WAY = 'one_way';

    const TRIP_ROUND_TRIP = 'round_trip';

    // Currency constants
    const CURRENCY_MYR = 'MYR';

    const CURRENCY_USD = 'USD';

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_PAID = 'paid';

    public static function getTransportTypes(): array
    {
        return [
            self::TYPE_GRAB => 'Grab / Ride-hailing',
            self::TYPE_TAXI => 'Taxi',
            self::TYPE_MRT => 'MRT',
            self::TYPE_LRT => 'LRT',
            self::TYPE_ECL => 'Electric Train (ECL)',
            self::TYPE_BUS => 'Bus',
            self::TYPE_TOLL => 'Toll',
            self::TYPE_PARKING => 'Parking',
            self::TYPE_OTHER => 'Other Transportation',
        ];
    }

    public static function getTripTypes(): array
    {
        return [
            self::TRIP_ONE_WAY => 'One Way',
            self::TRIP_ROUND_TRIP => 'Round Trip',
        ];
    }

    public static function getCurrencies(): array
    {
        return [
            self::CURRENCY_MYR => 'MYR - Malaysian Ringgit',
            self::CURRENCY_USD => 'USD - US Dollar',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_PAID => 'Paid',
        ];
    }

    // Default rates per km (can be customized per organization)
    public static function getDefaultRates(): array
    {
        return [
            self::TYPE_GRAB => 0.80, // RM per km
            self::TYPE_TAXI => 0.75, // RM per km
            self::TYPE_MRT => 0.50, // RM per km (approximate)
            self::TYPE_LRT => 0.45, // RM per km (approximate)
            self::TYPE_ECL => 0.60, // RM per km (approximate)
            self::TYPE_BUS => 0.30, // RM per km (approximate)
            self::TYPE_TOLL => 0.00, // Fixed amount, not per km
            self::TYPE_PARKING => 0.00, // Fixed amount, not per km
            self::TYPE_OTHER => 0.70, // RM per km
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Media collections for receipts
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('transport_receipts')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
            ->useDisk('public')
            ->withResponsiveImages();

        $this->addMediaCollection('supporting_documents')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
            ->useDisk('public');
    }

    // Calculate amount automatically
    public function calculateAmount(): void
    {
        if ($this->distance_km && $this->rate_per_km) {
            // For distance-based transportation
            $this->amount = $this->distance_km * $this->rate_per_km;
        }

        // Apply trip multiplier
        if ($this->trip_type === self::TRIP_ROUND_TRIP) {
            $this->amount *= 2;
        }

        // Apply number of trips
        if ($this->number_of_trips > 1) {
            $this->amount *= $this->number_of_trips;
        }
    }

    // Set default rate based on transport type
    public function setDefaultRate(): void
    {
        $defaultRates = self::getDefaultRates();
        if (isset($defaultRates[$this->transport_type]) && empty($this->rate_per_km)) {
            $this->rate_per_km = $defaultRates[$this->transport_type];
        }
    }

    // Accessors for display
    public function getTransportTypeDisplayAttribute(): string
    {
        $types = self::getTransportTypes();

        return $types[$this->transport_type] ?? $this->transport_type;
    }

    public function getTripTypeDisplayAttribute(): string
    {
        $types = self::getTripTypes();

        return $types[$this->trip_type] ?? $this->trip_type;
    }

    public function getCurrencyDisplayAttribute(): string
    {
        $currencies = self::getCurrencies();

        return $currencies[$this->currency] ?? $this->currency;
    }

    public function getStatusDisplayAttribute(): string
    {
        $statuses = self::getStatuses();

        return $statuses[$this->status] ?? $this->status;
    }

    public function getRouteAttribute(): string
    {
        return $this->from_location.' → '.$this->to_location;
    }

    // Business logic methods
    public function isDistanceBased(): bool
    {
        return in_array($this->transport_type, [
            self::TYPE_GRAB, self::TYPE_TAXI, self::TYPE_MRT,
            self::TYPE_LRT, self::TYPE_ECL, self::TYPE_BUS, self::TYPE_OTHER,
        ]);
    }

    public function isFixedAmount(): bool
    {
        return in_array($this->transport_type, [
            self::TYPE_TOLL, self::TYPE_PARKING,
        ]);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    // Event handlers
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->setDefaultRate();
            $model->calculateAmount();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'transport_type', 'amount', 'status', 'approver_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Transportation Claim {$eventName}")
            ->useLogName('transportation_claims');
    }
}
