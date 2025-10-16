<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationaryItemMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'stationary_item_id',
        'type',
        'quantity',
        'previous_stock',
        'new_stock',
        'notes',
        'movement_date',
        'reference',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
        'movement_date' => 'date',
    ];

    /**
     * Get the stationary item that owns the movement
     */
    public function stationaryItem(): BelongsTo
    {
        return $this->belongsTo(StationaryItem::class);
    }

    /**
     * Scope for incoming movements
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('type', ['in', 'return']);
    }

    /**
     * Scope for outgoing movements
     */
    public function scopeOutgoing($query)
    {
        return $query->where('type', 'out');
    }

    /**
     * Get movement type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'in' => 'Stock In',
            'out' => 'Stock Out',
            'adjustment' => 'Adjustment',
            'return' => 'Return',
            default => ucfirst($this->type)
        };
    }

    /**
     * Get net change
     */
    public function getNetChangeAttribute(): int
    {
        return match ($this->type) {
            'in', 'return' => $this->quantity,
            'out' => -$this->quantity,
            'adjustment' => $this->new_stock - $this->previous_stock,
            default => 0
        };
    }
}
