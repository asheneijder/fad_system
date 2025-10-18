<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ModelType extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'brand',
        'model_number',
        'specifications',
        'warranty_period',
        'status',
        'image',
        'sort_order',
    ];

    protected $casts = [
        'specifications' => 'array',
        'status' => 'boolean',
        'warranty_period' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category type that owns the model type
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the stationary items for the model type
     */
    public function stationaryItems(): HasMany
    {
        return $this->hasMany(StationaryItem::class);
    }

    /**
     * Scope for active models
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope by category type
     */
    public function scopeByCategoryType($query, $categoryTypeId)
    {
        return $query->where('category_id', $categoryTypeId);
    }

    /**
     * Scope by brand
     */
    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', 'like', "%{$brand}%");
    }

    /**
     * Get models with warranty
     */
    public function scopeWithWarranty($query)
    {
        return $query->where('warranty_period', '>', 0);
    }

    /**
     * Get models without warranty
     */
    public function scopeWithoutWarranty($query)
    {
        return $query->where('warranty_period', 0)->orWhereNull('warranty_period');
    }

    /**
     * Check if model has image
     */
    public function getHasImageAttribute(): bool
    {
        return ! empty($this->image);
    }

    /**
     * Get full model name with brand
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->brand} {$this->name}".($this->model_number ? " ({$this->model_number})" : '');
    }

    /**
     * Get warranty text
     */
    public function getWarrantyTextAttribute(): string
    {
        if (! $this->warranty_period) {
            return 'No warranty';
        }

        return $this->warranty_period.' month'.($this->warranty_period > 1 ? 's' : '');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'category_id', 'brand', 'model_number', 'specifications', 'warranty_period', 'status', 'image', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Model Type {$eventName}");
    }
}
