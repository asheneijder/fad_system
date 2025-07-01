<?php

namespace App\Models;

use App\Models\StationaryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationaryItemMovement extends Model
{
    protected $table = 'stationary_item_movements';

    protected $guarded = [];

    protected $appends = ['created_at_formatted'];

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->format('d-m-Y H:i');
    }
    
    public function stationaryItem(): BelongsTo
    {
        return $this->belongsTo(StationaryItem::class);
    }
}
