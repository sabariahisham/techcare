<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EnhancedUserRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Enhanced Permissions
        $this->createPermissions();

        // Create Enhanced Roles
        $this->createRoles();

        // Create Default Users
        $this->createUsers();

        // Assign Permissions to Roles
        $this->assignPermissionsToRoles();

        // Assign Roles to Users
        $this->assignRolesToUsers();
    }

    /**
     * Create enhanced permissions for the system.
     */
    private function createPermissions(): void
    {
        $permissions = [
            // User Management
            ['name' => 'user-management.view', 'module' => 'User Management', 'description' => 'View users list and details'],
            ['name' => 'user-management.create', 'module' => 'User Management', 'description' => 'Create new users'],
            ['name' => 'user-management.edit', 'module' => 'User Management', 'description' => 'Edit existing users'],
            ['name' => 'user-management.delete', 'module' => 'User Management', 'description' => 'Delete users'],
            ['name' => 'user-management.manage', 'module' => 'User Management', 'description' => 'Full user management access'],
            ['name' => 'user-management.reset-password', 'module' => 'User Management', 'description' => 'Reset user passwords'],
            ['name' => 'user-management.toggle-status', 'module' => 'User Management', 'description' => 'Activate/deactivate users'],

            // Role Management
            ['name' => 'role-management.view', 'module' => 'Role Management', 'description' => 'View roles list and details'],
            ['name' => 'role-management.create', 'module' => 'Role Management', 'description' => 'Create new roles'],
            ['name' => 'role-management.edit', 'module' => 'Role Management', 'description' => 'Edit existing roles'],
            ['name' => 'role-management.delete', 'module' => 'Role Management', 'description' => 'Delete roles'],
            ['name' => 'role-management.manage', 'module' => 'Role Management', 'description' => 'Full role management access'],
            ['name' => 'role-management.assign-permissions', 'module' => 'Role Management', 'description' => 'Assign permissions to roles'],

            // Permission Management
            ['name' => 'permission-management.view', 'module' => 'Permission Management', 'description' => 'View permissions list and details'],
            ['name' => 'permission-management.create', 'module' => 'Permission Management', 'description' => 'Create new permissions'],
            ['name' => 'permission-management.edit', 'module' => 'Permission Management', 'description' => 'Edit existing permissions'],
            ['name' => 'permission-management.delete', 'module' => 'Permission Management', 'description' => 'Delete permissions'],
            ['name' => 'permission-management.manage', 'module' => 'Permission Management', 'description' => 'Full permission management access'],

            // Admin Management
            ['name' => 'admin-management.view', 'module' => 'Admin Management', 'description' => 'View admin panels and settings'],
            ['name' => 'admin-management.manage', 'module' => 'Admin Management', 'description' => 'Full administrative access'],

            // Dashboard
            ['name' => 'dashboard.view', 'module' => 'Dashboard', 'description' => 'View dashboard'],
            ['name' => 'dashboard.manage', 'module' => 'Dashboard', 'description' => 'Manage dashboard settings'],

            // Ticket Management (existing functionality)
            ['name' => 'ticket-management.view', 'module' => 'Ticket Management', 'description' => 'View tickets'],
            ['name' => 'ticket-management.create', 'module' => 'Ticket Management', 'description' => 'Create new tickets'],
            ['name' => 'ticket-management.edit', 'module' => 'Ticket Management', 'description' => 'Edit tickets'],
            ['name' => 'ticket-management.delete', 'module' => 'Ticket Management', 'description' => 'Delete tickets'],
            ['name' => 'ticket-management.assign', 'module' => 'Ticket Management', 'description' => 'Assign tickets to users'],
            ['name' => 'ticket-management.close', 'module' => 'Ticket Management', 'description' => 'Close/resolve tickets'],

            // Configuration Management
            ['name' => 'configuration.view', 'module' => 'Configuration', 'description' => 'View system configurations'],
            ['name' => 'configuration.edit', 'module' => 'Configuration', 'description' => 'Edit system configurations'],

            // Report Management
            ['name' => 'report-management.view', 'module' => 'Report Management', 'description' => 'View reports'],
            ['name' => 'report-management.create', 'module' => 'Report Management', 'description' => 'Generate reports'],
            ['name' => 'report-management.export', 'module' => 'Report Management', 'description' => 'Export reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'guard_name' => 'web',
                    'module' => $permission['module'],
                    'description' => $permission['description'],
                    'category' => $this->getPermissionCategory($permission['module']),
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Create enhanced roles for the system.
     */
    private function createRoles(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'description' => 'Full system administrator with all permissions',
                'color' => 'danger',
                'icon' => 'bx-crown',
                'is_active' => true,
            ],
            [
                'name' => 'Administrator',
                'description' => 'System administrator with most permissions',
                'color' => 'warning',
                'icon' => 'bx-cog',
                'is_active' => true,
            ],
            [
                'name' => 'Pentadbir Agensi',
                'description' => 'Agency administrator role',
                'color' => 'primary',
                'icon' => 'bx-user-check',
                'is_active' => true,
            ],
            [
                'name' => 'Penyedia Agensi',
                'description' => 'Agency provider role',
                'color' => 'info',
                'icon' => 'bx-user',
                'is_active' => true,
            ],
            [
                'name' => 'Penyemak Agensi',
                'description' => 'Agency reviewer role',
                'color' => 'success',
                'icon' => 'bx-search',
                'is_active' => true,
            ],
            [
                'name' => 'Pengesah Agensi',
                'description' => 'Agency approver role',
                'color' => 'secondary',
                'icon' => 'bx-check-circle',
                'is_active' => true,
            ],
            [
                'name' => 'Pentadbir MOF',
                'description' => 'Ministry of Finance administrator role',
                'color' => 'primary',
                'icon' => 'bx-building',
                'is_active' => true,
            ],
            [
                'name' => 'Penyemak MOF',
                'description' => 'Ministry of Finance reviewer role',
                'color' => 'success',
                'icon' => 'bx-search-alt',
                'is_active' => true,
            ],
            [
                'name' => 'Pengesah MOF',
                'description' => 'Ministry of Finance approver role',
                'color' => 'secondary',
                'icon' => 'bx-check-double',
                'is_active' => true,
            ],
            [
                'name' => 'Staff',
                'description' => 'General staff member role',
                'color' => 'info',
                'icon' => 'bx-user-circle',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                [
                    'guard_name' => 'web',
                    'description' => $role['description'],
                    'color' => $role['color'],
                    'icon' => $role['icon'],
                    'is_active' => $role['is_active'],
                ]
            );
        }
    }

    /**
     * Create default users for the system.
     */
    private function createUsers(): void
    {
        $users = [
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@ehelp.gov.my',
                'password' => Hash::make('password123'),
                'agency' => 'System Administration',
                'seksyen' => 'IT Department',
                'jawatan' => 'System Administrator',
                'is_active' => true,
                'email_verified_at' => now(),
                'first_time_login' => false,
            ],
            [
                'name' => 'System Administrator',
                'email' => 'admin@ehelp.gov.my',
                'password' => Hash::make('password123'),
                'agency' => 'System Administration',
                'seksyen' => 'IT Department',
                'jawatan' => 'Administrator',
                'is_active' => true,
                'email_verified_at' => now(),
                'first_time_login' => false,
            ],
            [
                'name' => 'Ahmad Pentadbir',
                'email' => 'ahmad.pentadbir@agensi.gov.my',
                'password' => Hash::make('password123'),
                'ic_number' => '801234567890',
                'tel_bimbit' => '0123456789',
                'agency' => 'Agensi Contoh',
                'seksyen' => 'Pentadbiran',
                'jawatan' => 'Pentadbir Agensi',
                'is_active' => true,
                'email_verified_at' => now(),
                'first_time_login' => true,
            ],
            [
                'name' => 'Siti Penyedia',
                'email' => 'siti.penyedia@agensi.gov.my',
                'password' => Hash::make('password123'),
                'ic_number' => '850987654321',
                'tel_bimbit' => '0198765432',
                'agency' => 'Agensi Contoh',
                'seksyen' => 'Operasi',
                'jawatan' => 'Penyedia Agensi',
                'is_active' => true,
                'email_verified_at' => now(),
                'first_time_login' => true,
            ],
            [
                'name' => 'Muhammad Staff',
                'email' => 'muhammad.staff@agensi.gov.my',
                'password' => Hash::make('password123'),
                'ic_number' => '900123456789',
                'tel_bimbit' => '0156789012',
                'agency' => 'Agensi Contoh',
                'seksyen' => 'Operasi',
                'jawatan' => 'Staff Biasa',
                'is_active' => true,
                'email_verified_at' => now(),
                'first_time_login' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }

    /**
     * Assign permissions to roles.
     */
    private function assignPermissionsToRoles(): void
    {
        $rolePermissions = [
            'Super Admin' => Permission::all()->pluck('name')->toArray(),

            'Administrator' => [
                'user-management.view', 'user-management.create', 'user-management.edit',
                'user-management.reset-password', 'user-management.toggle-status',
                'role-management.view', 'role-management.create', 'role-management.edit',
                'permission-management.view', 'permission-management.create', 'permission-management.edit',
                'admin-management.view', 'admin-management.manage',
                'dashboard.view', 'dashboard.manage',
                'ticket-management.view', 'ticket-management.create', 'ticket-management.edit',
                'ticket-management.assign', 'ticket-management.close',
                'configuration.view', 'configuration.edit',
                'report-management.view', 'report-management.create', 'report-management.export',
            ],

            'Pentadbir Agensi' => [
                'user-management.view', 'user-management.create', 'user-management.edit',
                'user-management.toggle-status', 'user-management.reset-password',
                'role-management.view',
                'dashboard.view',
                'ticket-management.view', 'ticket-management.create', 'ticket-management.edit',
                'ticket-management.assign', 'ticket-management.close',
                'configuration.view',
                'report-management.view', 'report-management.create', 'report-management.export',
            ],

            'Penyedia Agensi' => [
                'dashboard.view',
                'ticket-management.view', 'ticket-management.create', 'ticket-management.edit',
                'report-management.view', 'report-management.create',
            ],

            'Penyemak Agensi' => [
                'dashboard.view',
                'ticket-management.view', 'ticket-management.edit',
                'report-management.view',
            ],

            'Pengesah Agensi' => [
                'dashboard.view',
                'ticket-management.view', 'ticket-management.edit', 'ticket-management.close',
                'report-management.view', 'report-management.create',
            ],

            'Pentadbir MOF' => [
                'dashboard.view', 'dashboard.manage',
                'ticket-management.view', 'ticket-management.create', 'ticket-management.edit',
                'ticket-management.assign', 'ticket-management.close',
                'configuration.view',
                'report-management.view', 'report-management.create', 'report-management.export',
            ],

            'Penyemak MOF' => [
                'dashboard.view',
                'ticket-management.view', 'ticket-management.edit',
                'report-management.view',
            ],

            'Pengesah MOF' => [
                'dashboard.view',
                'ticket-management.view', 'ticket-management.edit', 'ticket-management.close',
                'report-management.view', 'report-management.create',
            ],

            'Staff' => [
                'dashboard.view',
                'ticket-management.view', 'ticket-management.create',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->syncPermissions($permissions);
            }
        }
    }

    /**
     * Assign roles to users.
     */
    private function assignRolesToUsers(): void
    {
        $userRoles = [
            'superadmin@ehelp.gov.my' => 'Super Admin',
            'admin@ehelp.gov.my' => 'Administrator',
            'ahmad.pentadbir@agensi.gov.my' => 'Pentadbir Agensi',
            'siti.penyedia@agensi.gov.my' => 'Penyedia Agensi',
            'muhammad.staff@agensi.gov.my' => 'Staff',
        ];

        foreach ($userRoles as $email => $roleName) {
            $user = User::where('email', $email)->first();
            $role = Role::where('name', $roleName)->first();

            if ($user && $role) {
                $user->assignRole($role);
            }
        }
    }

    /**
     * Get permission category based on module.
     */
    private function getPermissionCategory(string $module): string
    {
        $categoryMap = [
            'User Management' => 'Admin',
            'Role Management' => 'Admin',
            'Permission Management' => 'Admin',
            'Admin Management' => 'System',
            'Dashboard' => 'System',
            'Ticket Management' => 'Module',
            'Configuration' => 'System',
            'Report Management' => 'Report',
        ];

        return $categoryMap[$module] ?? 'Module';
    }
}
