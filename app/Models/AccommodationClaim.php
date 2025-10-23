<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AccommodationClaim extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'accommodation_claims';

    protected $fillable = [
        'user_id',
        'hotel_name',
        'check_in_date',
        'check_out_date',
        'number_of_nights',
        'rate_per_night',
        'currency',
        'tax_percentage',
        'service_charge_percentage',
        'tax_amount',
        'service_charge_amount',
        'subtotal_amount',
        'total_amount',
        'purpose',
        'destination_city',
        'destination_country',
        'status',
        'approver_id',
        'approval_date',
        'rejection_reason',
        'remarks',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'approval_date' => 'datetime',
        'rate_per_night' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'service_charge_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'number_of_nights' => 'integer',
    ];

    // Currency constants
    const CURRENCY_MYR = 'MYR';

    const CURRENCY_USD = 'USD';

    const CURRENCY_SGD = 'SGD';

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_PAID = 'paid';

    public static function getCurrencies(): array
    {
        return [
            self::CURRENCY_MYR => 'MYR - Malaysian Ringgit',
            self::CURRENCY_USD => 'USD - US Dollar',
            self::CURRENCY_SGD => 'SGD - Singapore Dollar',
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

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Media collections for receipts and supporting documents
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hotel_receipts')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
            ->useDisk('public')
            ->withResponsiveImages();

        $this->addMediaCollection('supporting_documents')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
            ->useDisk('public');
    }

    // Calculate all amounts automatically
    public function calculateAmounts(): void
    {
        // Calculate number of nights
        if ($this->check_in_date && $this->check_out_date) {
            $this->number_of_nights = $this->check_in_date->diffInDays($this->check_out_date);
        }

        // Calculate subtotal (rate × nights)
        $this->subtotal_amount = $this->rate_per_night * $this->number_of_nights;

        // Calculate tax amount
        $this->tax_amount = ($this->subtotal_amount * $this->tax_percentage) / 100;

        // Calculate service charge amount
        $this->service_charge_amount = ($this->subtotal_amount * $this->service_charge_percentage) / 100;

        // Calculate total amount
        $this->total_amount = $this->subtotal_amount + $this->tax_amount + $this->service_charge_amount;
    }

    // Accessors for display
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

    public function getStayPeriodAttribute(): string
    {
        if (! $this->check_in_date || ! $this->check_out_date) {
            return 'N/A';
        }

        return $this->check_in_date->format('M j, Y').' to '.$this->check_out_date->format('M j, Y');
    }

    // Business logic methods
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function isApprovable(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_PENDING]);
    }

    // Event handlers
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->calculateAmounts();
        });
    }
}
