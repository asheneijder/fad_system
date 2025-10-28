<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'graph_id',
        'display_name',
        'surname',
        'mail',
        'given_name',
        'job_title',
        'department',
        'office_location',
        'status',
        'approver_id', // ADD THIS
        'can_approve', // ADD THIS
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'can_approve' => 'boolean', // ADD THIS
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'assigned_to');
    }

    /**
     * NEW: User belongs to an approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * NEW: User has many users they approve for
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'approver_id');
    }

    /**
     * NEW: User has many travel claims to approve
     */
    public function claimsToApprove(): HasMany
    {
        return $this->hasMany(TravelClaim::class, 'approver_id')
            ->where('approval_status', 'pending');
    }

    /**
     * NEW: Check if user can approve claims
     */
    public function canApproveClaims(): bool
    {
        return $this->can_approve || $this->subordinates()->exists();
    }

    /**
     * getUserPermissions
     *
     * @return void
     */
    public function getUserPermissions()
    {
        return $this->getAllPermissions()->mapWithKeys(fn ($permission) => [$permission['name'] => true]);
    }

    /**
     * Check if user is system admin (using Spatie permissions)
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasRole('system-admin');
    }

    /**
     * Check if user is super admin
     */
    public function isFadApprover(): bool
    {
        return $this->hasRole('fad-approver');
    }

    /**
     * Check if user is admin (any admin role)
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['system-admin', 'super-admin', 'admin']);
    }

    /**
     * Check if user is regular user
     */
    public function isRegularUser(): bool
    {
        return ! $this->isAdmin();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'serial_number', 'asset_tag', 'status']) // Add your asset fields
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Asset {$eventName}");
    }
}
