<?php

// app/Models/Asset.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'asset_tag',
        'serial_number',
        'model_id',
        'status',
        'purchase_date',
        'purchase_cost',
        'warranty_months',
        'notes',
        'image',
        'location',
        'assigned_to',
        'assigned_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'assigned_at' => 'datetime',
        'purchase_cost' => 'decimal:2',
        'warranty_months' => 'integer',
    ];

    // Relationships
    public function model(): BelongsTo
    {
        return $this->belongsTo(ModelType::class, 'model_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

    public function scopeWithWarranty($query)
    {
        return $query->where('warranty_months', '>', 0);
    }

    // Accessors
    public function getWarrantyExpiryAttribute()
    {
        if (! $this->purchase_date || ! $this->warranty_months) {
            return null;
        }

        return $this->purchase_date->addMonths($this->warranty_months);
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
        if (! $this->warranty_expiry) {
            return null;
        }

        $today = Carbon::today();
        $expiry = Carbon::parse($this->warranty_expiry);

        return $today->diffInDays($expiry, false); // Negative if expired
    }

    // Methods
    public function canBeAssigned(): bool
    {
        return $this->status === 'available';
    }

    public function canBeReturned(): bool
    {
        return $this->status === 'assigned';
    }

    public function assignToUser($userId, $assignedById, $condition, $notes = null): AssetAssignment
    {
        $assignment = AssetAssignment::create([
            'asset_id' => $this->id,
            'assigned_to' => $userId,
            'assigned_by' => $assignedById,
            'assigned_at' => now(),
            'condition_assigned' => $condition,
            'notes' => $notes,
        ]);

        $this->update([
            'status' => 'assigned',
            'assigned_to' => $userId,
            'assigned_at' => now(),
        ]);

        return $assignment;
    }

    public function returnFromAssignment($condition, $notes = null): bool
    {
        $assignment = $this->assignments()->active()->first();

        if (! $assignment) {
            return false;
        }

        $assignment->update([
            'returned_at' => now(),
            'condition_returned' => $condition,
            'notes' => $assignment->notes.($notes ? "\nReturn: ".$notes : ''),
        ]);

        $this->update([
            'status' => 'available',
            'assigned_to' => null,
            'assigned_at' => null,
        ]);

        return true;
    }
}
