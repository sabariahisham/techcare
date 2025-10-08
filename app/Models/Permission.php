<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'guard_name',
        'module',
        'description',
        'category',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'roles_count',
        'users_count',
        'display_name',
        'status_badge',
        'formatted_module',
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

            if (!isset($model->is_active)) {
                $model->is_active = true;
            }

            // Auto-generate module from permission name if not set
            if (!$model->module && $model->name) {
                $model->module = $model->extractModuleFromName($model->name);
            }

            // Auto-set sort order
            if (!$model->sort_order) {
                $model->sort_order = static::max('sort_order') + 10;
            }
        });
    }

    /**
     * Get the number of roles assigned to this permission.
     */
    public function getRolesCountAttribute(): int
    {
        return $this->roles()->count();
    }

    /**
     * Get the number of users with this permission.
     */
    public function getUsersCountAttribute(): int
    {
        // Count users who have this permission directly or through roles
        $directUsers = $this->users()->count();
        $roleUsers = \App\Models\User::whereHas('roles.permissions', function($query) {
            $query->where('permissions.id', $this->id);
        })->count();

        return $directUsers + $roleUsers;
    }

    /**
     * Get the display name for the permission.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->description ?? $this->formatPermissionName($this->name);
    }

    /**
     * Get the permission's status badge.
     */
    public function getStatusBadgeAttribute(): array
    {
        return [
            'status' => $this->is_active ? 'Active' : 'Inactive',
            'class' => $this->is_active ? 'badge bg-label-success' : 'badge bg-label-secondary',
        ];
    }

    /**
     * Get the formatted module name.
     */
    public function getFormattedModuleAttribute(): string
    {
        if (!$this->module) {
            return 'General';
        }

        return ucwords(str_replace(['-', '_'], ' ', $this->module));
    }

    /**
     * Check if permission is active.
     */
    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    /**
     * Check if permission is inactive.
     */
    public function isInactive(): bool
    {
        return !$this->isActive();
    }

    /**
     * Activate the permission.
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the permission.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Scope to filter active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive permissions.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter by module.
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to exclude certain modules (like module-list).
     */
    public function scopeExcludeModules($query, $modules = [])
    {
        if (empty($modules)) {
            $modules = ['Module-List', 'module-list'];
        }

        return $query->whereNotIn('module', $modules);
    }

    /**
     * Scope to search permissions.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhere('module', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope to order by module and name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('module')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get permissions grouped by module.
     */
    public static function getGroupedPermissions(array $excludeModules = ['Module-List', 'module-list'])
    {
        return static::excludeModules($excludeModules)
                    ->active()
                    ->ordered()
                    ->get()
                    ->groupBy('formatted_module');
    }

    /**
     * Get permission statistics.
     */
    public function getStats(): array
    {
        return [
            'roles_count' => $this->roles()->count(),
            'users_count' => $this->getUsersCountAttribute(),
            'created_date' => $this->created_at->format('d M Y'),
            'last_updated' => $this->updated_at->format('d M Y'),
            'is_active' => $this->is_active,
            'module' => $this->formatted_module,
        ];
    }

    /**
     * Get roles with this permission along with their details.
     */
    public function getRolesWithDetails()
    {
        return $this->roles()
                   ->select(['id', 'name', 'description'])
                   ->with(['users' => function ($query) {
                       $query->select(['id', 'name']);
                   }])
                   ->get();
    }

    /**
     * Check if permission can be deleted safely.
     */
    public function canBeDeleted(): array
    {
        $errors = [];

        if ($this->roles()->count() > 0) {
            $errors[] = 'Permission is assigned to ' . $this->roles()->count() . ' role(s)';
        }

        if ($this->users()->count() > 0) {
            $errors[] = 'Permission is directly assigned to ' . $this->users()->count() . ' user(s)';
        }

        return [
            'can_delete' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Extract module name from permission name.
     */
    private function extractModuleFromName(string $name): string
    {
        // Extract module from permission name patterns like "module.action"
        $parts = explode('.', $name);
        if (count($parts) >= 2) {
            return ucwords(str_replace(['-', '_'], ' ', $parts[0]));
        }

        // Extract from patterns like "module-action"
        $parts = explode('-', $name);
        if (count($parts) >= 2) {
            return ucwords(str_replace(['-', '_'], ' ', $parts[0]));
        }

        return 'General';
    }

    /**
     * Format permission name for display.
     */
    private function formatPermissionName(string $name): string
    {
        // Remove common suffixes and format nicely
        $formatted = str_replace(['.', '-', '_'], ' ', $name);
        $formatted = ucwords($formatted);

        // Replace common action words
        $replacements = [
            'View' => 'View',
            'Create' => 'Create',
            'Edit' => 'Edit',
            'Update' => 'Update',
            'Delete' => 'Delete',
            'Index' => 'List',
            'Show' => 'View',
            'Store' => 'Create',
            'Destroy' => 'Delete',
        ];

        foreach ($replacements as $search => $replace) {
            $formatted = str_replace($search, $replace, $formatted);
        }

        return $formatted;
    }

    /**
     * Create permissions for a module with standard CRUD operations.
     */
    public static function createModulePermissions(string $module, array $actions = ['view', 'create', 'edit', 'delete'], string $description = null): array
    {
        $permissions = [];
        $sortOrder = static::max('sort_order') ?? 0;

        foreach ($actions as $action) {
            $sortOrder += 10;

            $permission = static::firstOrCreate([
                'name' => $module . '.' . $action,
                'guard_name' => 'web',
            ], [
                'module' => ucwords(str_replace(['-', '_'], ' ', $module)),
                'description' => $description ? $description . ' - ' . ucfirst($action) : null,
                'category' => 'Module',
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);

            $permissions[] = $permission;
        }

        return $permissions;
    }

    /**
     * Get permission categories.
     */
    public static function getCategories(): array
    {
        return [
            'Module' => 'Module Permissions',
            'System' => 'System Permissions',
            'Admin' => 'Administration Permissions',
            'User' => 'User Permissions',
            'Content' => 'Content Management',
            'Report' => 'Reporting Permissions',
        ];
    }

    /**
     * Get common permission actions.
     */
    public static function getCommonActions(): array
    {
        return [
            'view' => 'View/Read',
            'create' => 'Create/Add',
            'edit' => 'Edit/Update',
            'delete' => 'Delete/Remove',
            'manage' => 'Full Management',
            'export' => 'Export Data',
            'import' => 'Import Data',
            'approve' => 'Approve/Authorize',
        ];
    }
}
