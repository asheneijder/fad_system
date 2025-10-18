<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cart extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'stationary_item_id',
        'quantity',
        'notes',
    ];

    /**
     * Get the user that owns the cart item
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the stationary item
     */
    public function stationaryItem(): BelongsTo
    {
        return $this->belongsTo(StationaryItem::class);
    }

    /**
     * Get line total
     */
    public function getLineTotalAttribute(): float
    {
        return $this->quantity * ($this->stationaryItem->cost_price ?? 0);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'stationary_item_id', 'quantity', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Cart {$eventName}");
    }
}
