<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
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
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
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
