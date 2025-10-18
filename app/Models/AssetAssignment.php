<?php

// app/Models/AssetAssignment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssetAssignment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'asset_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'returned_at',
        'notes',
        'condition_assigned',
        'condition_returned',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('returned_at');
    }

    public function scopeHistory($query)
    {
        return $query->whereNotNull('returned_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('assigned_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return is_null($this->returned_at);
    }

    public function getAssignmentDurationAttribute(): ?string
    {
        if (! $this->assigned_at) {
            return null;
        }

        $endDate = $this->returned_at ?? now();

        return $this->assigned_at->diffForHumans($endDate, true);
    }

    // Methods
    public function markReturned($condition, $notes = null): bool
    {
        if ($this->returned_at) {
            return false; // Already returned
        }

        $this->update([
            'returned_at' => now(),
            'condition_returned' => $condition,
            'notes' => $this->notes.($notes ? "\nReturn: ".$notes : ''),
        ]);

        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['asset_id', 'assigned_to', 'assigned_by', 'assigned_at', 'returned_at', 'notes', 'condition_assigned', 'condition_returned'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Asset Assignment {$eventName}");
    }
}
