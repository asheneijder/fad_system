<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Asset extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'asset_name',
        'asset_tag_no',
        'serial_no',
        'model_type_id',
        'category_type_id',
        'status',
        'qty',
        'location',
        'location_2',
        'purchase_cost',
        'current_value',
        'purchase_date',
        'estimated_life',
        'estimated_life_days',
        'fully_depreciated_date',
        'depreciation_cost',
        'last_sighting_date',
        'assigned_to',
        'assigned_at',
        'updated_by',
        'notes',
        'image',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'fully_depreciated_date' => 'date',
        'last_sighting_date' => 'date',
        'assigned_at' => 'datetime',
        'purchase_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
        'depreciation_cost' => 'decimal:2',
        'estimated_life' => 'integer',
        'estimated_life_days' => 'integer',
        'qty' => 'integer',
    ];

    // Relationships
    public function model(): BelongsTo
    {
        return $this->belongsTo(ModelType::class, 'model_type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)->latestOfMany();
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    public function scopeRetired($query)
    {
        return $query->where('status', 'retired');
    }

    public function scopeWithDepreciation($query)
    {
        return $query->where('depreciation_cost', '>', 0);
    }

    public function scopeNeedsSighting($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_sighting_date')
                ->orWhere('last_sighting_date', '<', now()->subYear());
        });
    }

    // Accessors
    public function getWarrantyExpiryAttribute()
    {
        if (!$this->purchase_date || !$this->model?->warranty_period) {
            return null;
        }

        return $this->purchase_date->addMonths($this->model->warranty_period);
    }

    public function getIsWarrantyExpiredAttribute()
    {
        return $this->warranty_expiry && $this->warranty_expiry->isPast();
    }

    public function getIsWarrantyExpiringSoonAttribute()
    {
        return $this->warranty_expiry &&
            $this->warranty_expiry->isFuture() &&
            $this->warranty_expiry->diffInDays(now()) <= 30;
    }

    public function getDaysUntilWarrantyExpiryAttribute()
    {
        if (!$this->warranty_expiry) {
            return null;
        }

        return now()->diffInDays($this->warranty_expiry, false);
    }

    public function getIsFullyDepreciatedAttribute()
    {
        return $this->fully_depreciated_date && $this->fully_depreciated_date->isPast();
    }

    public function getRemainingLifeDaysAttribute()
    {
        if (!$this->estimated_life_days || !$this->purchase_date) {
            return null;
        }

        $daysSincePurchase = $this->purchase_date->diffInDays(now());

        return max(0, $this->estimated_life_days - $daysSincePurchase);
    }

    public function getRemainingLifePercentageAttribute()
    {
        if (!$this->estimated_life_days || $this->estimated_life_days <= 0) {
            return 100;
        }

        $remainingDays = $this->remaining_life_days;

        return ($remainingDays / $this->estimated_life_days) * 100;
    }

    public function getAnnualDepreciationAttribute()
    {
        if (!$this->purchase_cost || !$this->estimated_life) {
            return 0;
        }

        return $this->estimated_life > 0 ? $this->purchase_cost / $this->estimated_life : 0;
    }

    public function getMonthlyDepreciationAttribute()
    {
        return $this->annual_depreciation / 12;
    }

    public function getDaysSinceLastSightingAttribute()
    {
        if (!$this->last_sighting_date) {
            return null;
        }

        return $this->last_sighting_date->diffInDays(now());
    }

    public function getNeedsSightingAttribute()
    {
        return !$this->last_sighting_date || $this->last_sighting_date->diffInDays(now()) > 365;
    }

    // Methods
    public function canBeAssigned(): bool
    {
        return in_array($this->status, ['active', 'available']);
    }

    public function canBeReturned(): bool
    {
        return $this->status === 'assigned';
    }

    public function assignToUser($userId, $assignedById, $condition, $notes = null, $assignedAt = null): AssetAssignment
    {
        $assignmentDate = $assignedAt ? \Carbon\Carbon::parse($assignedAt) : now();

        $assignment = AssetAssignment::create([
            'asset_id' => $this->id,
            'assigned_to' => $userId,
            'assigned_by' => $assignedById,
            'assigned_at' => $assignmentDate,
            'condition_assigned' => $condition,
            'notes' => $notes,
        ]);

        $this->update([
            'status' => 'assigned',
            'assigned_to' => $userId,
            'assigned_at' => $assignmentDate,
            'updated_by' => $assignedById,
        ]);

        return $assignment;
    }

    public function returnFromAssignment($condition, $notes = null): bool
    {
        $assignment = $this->assignments()->active()->first();

        if (!$assignment) {
            return false;
        }

        $assignment->update([
            'returned_at' => now(),
            'condition_returned' => $condition,
            'notes' => $assignment->notes . ($notes ? "\nReturn: " . $notes : ''),
        ]);

        $this->update([
            'status' => 'available',
            'assigned_to' => null,
            'assigned_at' => null,
            'updated_by' => auth()->id(),
        ]);

        return true;
    }

    public function calculateDepreciation(): void
    {
        if (!$this->purchase_date || !$this->estimated_life_days || !$this->purchase_cost) {
            return;
        }

        $daysSincePurchase = $this->purchase_date->diffInDays(now());
        $depreciationRate = $this->purchase_cost / $this->estimated_life_days;
        $this->depreciation_cost = min($this->purchase_cost, $depreciationRate * $daysSincePurchase);
        $this->current_value = max(0, $this->purchase_cost - $this->depreciation_cost);

        // Update fully depreciated date
        if ($this->estimated_life_days > 0) {
            $this->fully_depreciated_date = $this->purchase_date->addDays($this->estimated_life_days);
        }
    }

    public function updateSighting(): void
    {
        $this->update([
            'last_sighting_date' => now(),
            'updated_by' => auth()->id(),
        ]);
    }

    public function markAsRetired(): void
    {
        $this->update([
            'status' => 'retired',
            'current_value' => 0,
            'updated_by' => auth()->id(),
        ]);
    }

    // Boot method for automatic depreciation calculation
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($asset) {
            if ($asset->isDirty(['purchase_date', 'purchase_cost', 'estimated_life_days'])) {
                $asset->calculateDepreciation();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'asset_name',
                'asset_tag_no',
                'serial_no',
                'model_type_id',
                'category_type_id',
                'status',
                'qty',
                'location',
                'location_2',
                'purchase_cost',
                'current_value',
                'purchase_date',
                'estimated_life',
                'estimated_life_days',
                'fully_depreciated_date',
                'depreciation_cost',
                'last_sighting_date',
                'assigned_to',
                'assigned_at',
                'updated_by',
                'notes',
                'image',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Asset {$eventName}");
    }
}
