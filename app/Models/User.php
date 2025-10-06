<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Jetstream\HasProfilePhoto;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'ic_number',
        'phone_number',
        'office_phone_number',
        'scheme_and_grade',
        'agency',
        'position',
        'department',
        'section',
        'unit',
        'position_title',
        'last_login_at',
        'last_login_ip',
        'password_change_at',
        'first_time_login',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
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
            'last_login_at' => 'datetime',
            'password_change_at' => 'datetime',
            'first_time_login' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'full_name',
        'display_role',
        'status_badge',
    ];

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->name ?? 'Unknown User';
    }

    /**
     * Get the user's display role.
     *
     * @return string
     */
    public function getDisplayRoleAttribute(): string
    {
        return $this->roles->first()?->name ?? 'No Role';
    }

    /**
     * Get the user's status badge.
     *
     * @return array
     */
    public function getStatusBadgeAttribute(): array
    {
        return [
            'status' => $this->is_active ? 'Active' : 'Inactive',
            'class' => $this->is_active ? 'badge bg-label-success' : 'badge bg-label-secondary',
        ];
    }

    /**
     * Check if user is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if user is inactive.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return !$this->is_active;
    }

    /**
     * Activate the user.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the user.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Check if user has verified their email.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if this is user's first time login.
     *
     * @return bool
     */
    public function isFirstTimeLogin(): bool
    {
        return $this->first_time_login;
    }

    /**
     * Mark first time login as completed.
     *
     * @return bool
     */
    public function completeFirstLogin(): bool
    {
        return $this->update(['first_time_login' => false]);
    }

    /**
     * Update last login information.
     *
     * @param string|null $ip
     * @return bool
     */
    public function updateLastLogin(?string $ip = null): bool
    {
        return $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * Scope to filter active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter verified users.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope to filter unverified users.
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Scope to search users.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('ic_number', 'LIKE', "%{$search}%")
              ->orWhere('phone_number', 'LIKE', "%{$search}%")
              ->orWhere('agency', 'LIKE', "%{$search}%")
              ->orWhere('position', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope to filter users by role.
     */
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Get user's primary role name.
     *
     * @return string|null
     */
    public function getPrimaryRole(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param array|string $roles
     * @return bool
     */
    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    /**
     * Override the hasPermissionTo method to give all permissions to Administrator role.
     *
     * @param string|\Spatie\Permission\Contracts\Permission $permission
     * @param string|null $guardName
     * @return bool
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Check if user has Administrator role - if so, grant all permissions
        if ($this->hasRole('Administrator')) {
            return true;
        }

        // Otherwise, use the default permission checking logic
        return parent::hasPermissionTo($permission, $guardName);
    }

    /**
     * Check if user is super admin (has Administrator role).
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Administrator');
    }

    /**
     * Get formatted contact information.
     *
     * @return array
     */
    public function getContactInfo(): array
    {
        return [
            'email' => $this->email,
            'mobile_phone' => $this->phone_number,
            'office_phone' => $this->office_phone_number,
            'ic_number' => $this->ic_number,
        ];
    }

    /**
     * Get formatted work information.
     *
     * @return array
     */
    public function getWorkInfo(): array
    {
        return [
            'agency' => $this->agency,
            'department' => $this->department,
            'section' => $this->section,
            'unit' => $this->unit,
            'position' => $this->position,
            'position_title' => $this->position_title,
            'scheme_and_grade' => $this->scheme_and_grade,
        ];
    }

    /**
     * Get user statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_logins' => 0, // You can track this with a separate model/table
            'last_login' => $this->last_login_at?->format('d M Y, h:i A'),
            'member_since' => $this->created_at->format('d M Y'),
            'roles_count' => $this->roles->count(),
            'permissions_count' => $this->getAllPermissions()->count(),
        ];
    }
}
