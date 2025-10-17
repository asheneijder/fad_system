<?php

// app/Models/MaintenanceRecord.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'scheduled_date',
        'completed_date',
        'cost',
        'notes',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'cost' => 'decimal:2',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_date', '<', now());
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'scheduled' &&
               $this->scheduled_date &&
               $this->scheduled_date->isPast();
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->scheduled_date || ! $this->completed_date) {
            return null;
        }

        return $this->scheduled_date->diffForHumans($this->completed_date, true);
    }

    // Methods
    public function markInProgress(): bool
    {
        if ($this->status === 'scheduled') {
            $this->update(['status' => 'in_progress']);

            return true;
        }

        return false;
    }

    public function markCompleted($cost = null, $notes = null): bool
    {
        if (in_array($this->status, ['scheduled', 'in_progress'])) {
            $this->update([
                'status' => 'completed',
                'completed_date' => now(),
                'cost' => $cost ?? $this->cost,
                'notes' => $notes ? $this->notes."\nCompleted: ".$notes : $this->notes,
            ]);

            return true;
        }

        return false;
    }

    public function cancel($reason = null): bool
    {
        if ($this->status !== 'completed') {
            $this->update([
                'status' => 'cancelled',
                'notes' => $reason ? $this->notes."\nCancelled: ".$reason : $this->notes,
            ]);

            return true;
        }

        return false;
    }
}
