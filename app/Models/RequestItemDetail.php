<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestItemDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_item_id',
        'stationary_item_id',
        'quantity',
        'notes',
        'unit_price',
        'approved_quantity', // Add this
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'approved_quantity' => 'integer',
    ];

    /**
     * Get the request item
     */
    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(RequestItem::class);
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
        $quantity = $this->approved_quantity ?? $this->quantity;

        return $quantity * $this->unit_price;
    }

    /**
     * Get the final approved quantity
     */
    public function getFinalQuantityAttribute(): int
    {
        return $this->approved_quantity ?? $this->quantity;
    }
}
