<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class License extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'license_name',
        'product_key',
        'expiration_date',
        'licensed_email',
        'licensed_name',
        'manufacturer',
        'min_qty',
        'total_qty',
        'available_qty',
        'status',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'status' => 'boolean',
        'min_qty' => 'integer',
        'total_qty' => 'integer',
        'available_qty' => 'integer',
    ];

    /**
     * Scope for active licenses
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for expired licenses
     */
    public function scopeExpired($query)
    {
        return $query->where('expiration_date', '<', now());
    }

    /**
     * Scope for expiring soon licenses (within 30 days)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->whereBetween('expiration_date', [now(), now()->addDays(30)]);
    }

    /**
     * Check if license is expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->expiration_date->isPast();
    }

    /**
     * Check if license is expiring soon (within 30 days)
     */
    public function getIsExpiringSoonAttribute()
    {
        return $this->expiration_date->isFuture() &&
               $this->expiration_date->diffInDays(now()) <= 30;
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpirationAttribute()
    {
        return $this->expiration_date->diffInDays(now(), false) * -1;
    }

    /**
     * Check if license is low stock
     */
    public function getIsLowStockAttribute()
    {
        return $this->available_qty <= $this->min_qty && $this->available_qty > 0;
    }

    /**
     * Check if license is out of stock
     */
    public function getIsOutOfStockAttribute()
    {
        return $this->available_qty === 0;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute()
    {
        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        } elseif ($this->is_low_stock) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Get overall status considering expiration and stock
     */
    public function getOverallStatusAttribute()
    {
        if (! $this->status) {
            return 'inactive';
        }

        if ($this->is_expired) {
            return 'expired';
        }

        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        }

        if ($this->is_expiring_soon) {
            return 'expiring_soon';
        }

        if ($this->is_low_stock) {
            return 'low_stock';
        }

        return 'active';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['license_name', 'product_key', 'expiration_date', 'licensed_email', 'licensed_name', 'manufacturer', 'min_qty', 'total_qty', 'available_qty', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "License {$eventName}");
    }
}
