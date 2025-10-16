<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Asset extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'asset_name',
        'asset_tag_no',
        'serial_no',
        'model_type_id',
        'qty',
        'status',
        'description',
        'purchase_date',
        'purchase_price',
        'warranty_expiry',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    // Relationships
    public function modelType()
    {
        return $this->belongsTo(ModelType::class);
    }

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('returned_at')->latest();
    }

    // Get users through assignments (corrected relationship)
    public function assignedUsers()
    {
        return $this->hasManyThrough(
            User::class,
            AssetAssignment::class,
            'asset_id', // Foreign key on AssetAssignment table
            'id', // Foreign key on User table
            'id', // Local key on Asset table
            'assigned_to' // Local key on AssetAssignment table
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Methods
    public function isAvailable()
    {
        return $this->status === 'available';
    }

    public function isAssigned()
    {
        return $this->status === 'assigned';
    }

    public function getAssignedUser()
    {
        return $this->currentAssignment?->user;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif']);
    }
}
