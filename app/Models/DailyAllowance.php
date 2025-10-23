<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DailyAllowance extends Model implements HasMedia
{
    use InteractsWithMedia;

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'claim_date',
        'allowance_type', // full_day, breakfast, lunch, dinner
        'currency', // MYR, USD, etc.
        'daily_rate',
        'claim_percentage',
        'claim_amount',
        'purpose',
        'destination',
        'status',
        'approver_id',
        'approval_date',
        'rejection_reason',
        'submitted_at',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'approval_date' => 'datetime',
        'submitted_at' => 'datetime',
        'daily_rate' => 'decimal:2',
        'claim_percentage' => 'decimal:2',
        'claim_amount' => 'decimal:2',
    ];

    // Allowance type constants
    const TYPE_FULL_DAY = 'full_day';

    const TYPE_BREAKFAST = 'breakfast';

    const TYPE_LUNCH = 'lunch';

    const TYPE_DINNER = 'dinner';

    // Currency constants
    const CURRENCY_MYR = 'MYR';

    const CURRENCY_USD = 'USD';

    public static function getAllowanceTypes(): array
    {
        return [
            self::TYPE_FULL_DAY => 'Full Day (100%) - Kelayakan Penuh',
            self::TYPE_BREAKFAST => 'Breakfast (20%)',
            self::TYPE_LUNCH => 'Lunch (40%)',
            self::TYPE_DINNER => 'Dinner (40%)',
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
            self::STATUS_SUBMITTED => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public static function getPercentageByType(string $type): float
    {
        return match ($type) {
            self::TYPE_FULL_DAY => 100.00,
            self::TYPE_BREAKFAST => 20.00,
            self::TYPE_LUNCH => 40.00,
            self::TYPE_DINNER => 40.00,
            default => 0.00,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipts')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
            ->useDisk('public');
    }

    // Calculate claim amount automatically
    public function calculateClaimAmount(): void
    {
        $percentage = self::getPercentageByType($this->allowance_type);
        $this->claim_percentage = $percentage;
        $this->claim_amount = ($this->daily_rate * $percentage) / 100;
    }

    // Get display text for allowance type with percentage
    public function getAllowanceTypeDisplayAttribute(): string
    {
        $types = self::getAllowanceTypes();

        return $types[$this->allowance_type] ?? $this->allowance_type;
    }

    // Get display text for currency
    public function getCurrencyDisplayAttribute(): string
    {
        $currencies = self::getCurrencies();

        return $currencies[$this->currency] ?? $this->currency;
    }

    // Get display text for status
    public function getStatusDisplayAttribute(): string
    {
        $statuses = self::getStatuses();

        return $statuses[$this->status] ?? $this->status;
    }

    // Scope for draft claims
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    // Scope for submitted claims
    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    // Scope for approved claims
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    // Check if claim can be edited
    public function getCanEditAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    // Check if claim can be submitted
    public function getCanSubmitAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    // Check if claim can be deleted
    public function getCanDeleteAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
