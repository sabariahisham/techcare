<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'is_active',
        'color',
        'icon',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'users_count',
        'permissions_count',
        'display_name',
        'status_badge',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->guard_name) {
                $model->guard_name = 'web';
            }

            if (!$model->is_active) {
                $model->is_active = true;
            }
        });
    }

    /**
     * Get the number of users assigned to this role.
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Get the number of permissions assigned to this role.
     */
    public function getPermissionsCountAttribute(): int
    {
        return $this->permissions()->count();
    }

    /**
     * Get the display name for the role.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->description ?? ucwords(str_replace('-', ' ', $this->name));
    }

    /**
     * Get the role's status badge.
     */
    public function getStatusBadgeAttribute(): array
    {
        return [
            'status' => $this->is_active ? 'Active' : 'Inactive',
            'class' => $this->is_active ? 'badge bg-label-success' : 'badge bg-label-secondary',
        ];
    }

    /**
     * Check if role is active.
     */
    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    /**
     * Check if role is inactive.
     */
    public function isInactive(): bool
    {
        return !$this->isActive();
    }

    /**
     * Activate the role.
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the role.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Scope to filter active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive roles.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to exclude system roles.
     */
    public function scopeExcludeSystem($query)
    {
        return $query->whereNotIn('name', ['Super Admin', 'Administrator']);
    }

    /**
     * Scope to search roles.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Check if role is system role that shouldn't be deleted.
     */
    public function isSystemRole(): bool
    {
        return in_array($this->name, ['Super Admin', 'Administrator']);
    }

    /**
     * Get role statistics.
     */
    public function getStats(): array
    {
        return [
            'users_count' => $this->users()->count(),
            'permissions_count' => $this->permissions()->count(),
            'created_date' => $this->created_at->format('d M Y'),
            'last_updated' => $this->updated_at->format('d M Y'),
            'is_active' => $this->is_active,
        ];
    }

    /**
     * Get permissions grouped by module.
     */
    public function getGroupedPermissions(): array
    {
        return $this->permissions()
                   ->get()
                   ->groupBy('module')
                   ->map(function ($permissions) {
                       return $permissions->pluck('name')->toArray();
                   })
                   ->toArray();
    }

    /**
     * Sync permissions and return detailed result.
     */
    public function syncPermissionsWithDetails(array $permissions): array
    {
        $oldPermissions = $this->permissions->pluck('name')->toArray();
        $this->syncPermissions($permissions);
        $newPermissions = $this->fresh()->permissions->pluck('name')->toArray();

        return [
            'added' => array_diff($newPermissions, $oldPermissions),
            'removed' => array_diff($oldPermissions, $newPermissions),
            'total' => count($newPermissions),
        ];
    }

    /**
     * Get users with this role along with their details.
     */
    public function getUsersWithDetails()
    {
        return $this->users()
                   ->select(['id', 'name', 'email', 'is_active', 'last_login_at'])
                   ->with(['roles' => function ($query) {
                       $query->select(['id', 'name']);
                   }])
                   ->get();
    }

    /**
     * Check if role can be deleted safely.
     */
    public function canBeDeleted(): array
    {
        $errors = [];

        if ($this->isSystemRole()) {
            $errors[] = 'System roles cannot be deleted';
        }

        if ($this->users()->count() > 0) {
            $errors[] = 'Role is assigned to ' . $this->users()->count() . ' user(s)';
        }

        return [
            'can_delete' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get role color for UI display.
     */
    public function getRoleColor(): string
    {
        return $this->color ?? $this->getDefaultRoleColor();
    }

    /**
     * Get default color based on role name.
     */
    private function getDefaultRoleColor(): string
    {
        $colorMap = [
            'Super Admin' => 'danger',
            'Administrator' => 'warning',
            'Manager' => 'primary',
            'Staff' => 'info',
            'User' => 'secondary',
        ];

        return $colorMap[$this->name] ?? 'primary';
    }

    /**
     * Get role icon for UI display.
     */
    public function getRoleIcon(): string
    {
        return $this->icon ?? $this->getDefaultRoleIcon();
    }

    /**
     * Get default icon based on role name.
     */
    private function getDefaultRoleIcon(): string
    {
        $iconMap = [
            'Super Admin' => 'bx-crown',
            'Administrator' => 'bx-cog',
            'Manager' => 'bx-user-check',
            'Staff' => 'bx-user',
            'User' => 'bx-user-circle',
        ];

        return $iconMap[$this->name] ?? 'bx-user-circle';
    }
}
