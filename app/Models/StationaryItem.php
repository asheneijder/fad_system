<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StationaryItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'category',
        'unit',
        'sku',
        'min_stock',
        'current_stock',
        'cost_price',
        'selling_price',
        'supplier',
        'location',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'min_stock' => 'integer',
        'current_stock' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    /**
     * Get the movements for the stationary item
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StationaryItemMovement::class);
    }

    /**
     * Get the request item details for this stationary item
     */
    public function requestItemDetails(): HasMany
    {
        return $this->hasMany(RequestItemDetail::class, 'stationary_item_id');
    }

    /**
     * Scope for active items
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= min_stock AND current_stock > 0');
    }

    /**
     * Scope for out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', 0);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if item is low stock
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock && $this->current_stock > 0;
    }

    /**
     * Check if item is out of stock
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->current_stock === 0;
    }

    /**
     * Get stock value
     */
    public function getStockValueAttribute(): float
    {
        return $this->current_stock * $this->cost_price;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        } elseif ($this->is_low_stock) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'category', 'unit', 'sku', 'min_stock', 'current_stock', 'cost_price', 'selling_price', 'supplier', 'location', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Stationary Item {$eventName}");
    }
}
