<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TravelClaim extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'travel_claims';

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'registration_plate_number',
        'cubic_capacity',
        'date_of_travel',
        'end_date_of_travel',
        'is_multiple_days',
        'travel_from',
        'travel_to',
        'purpose',
        'total_distance',
        'rate_per_km',
        'status',
        'total_cost',
        'approver_id',
        'approval_date',
        'rejection_reason',
        'claim_date',
        'travel_legs_data', // Add this
    ];

    protected $casts = [
        'date_of_travel' => 'date',
        'end_date_of_travel' => 'date',
        'approval_date' => 'datetime',
        'claim_date' => 'datetime',
        'is_multiple_days' => 'boolean',
        'total_distance' => 'decimal:2',
        'rate_per_km' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'travel_legs_data' => 'array', // Add this cast
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipts')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
            ->useDisk('public')
            ->withResponsiveImages();
    }

    // Accessor for travel legs
    public function getTravelLegsAttribute()
    {
        return $this->travel_legs_data ?? [];
    }

    // Get route summary
    public function getRouteSummaryAttribute(): string
    {
        $legs = $this->travel_legs;

        if (empty($legs)) {
            return $this->travel_from.' → '.$this->travel_to;
        }

        $locations = [];
        foreach ($legs as $leg) {
            $locations[] = $leg['from'];
        }

        // Add the final destination
        $lastLeg = end($legs);
        $locations[] = $lastLeg['to'];

        return implode(' → ', $locations);
    }

    // Calculate total distance from legs
    public function calculateTotalDistanceFromLegs(): float
    {
        $legs = $this->travel_legs;
        if (empty($legs)) {
            return $this->total_distance;
        }

        return array_sum(array_column($legs, 'distance'));
    }
}
