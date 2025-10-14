<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class EnhancedUserRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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
            ['name' => 'pengguna.view', 'module' => 'Pengurusan Pengguna', 'description' => 'View users list and details'],
            ['name' => 'pengguna.create', 'module' => 'Pengurusan Pengguna', 'description' => 'Create new users'],
            ['name' => 'pengguna.edit', 'module' => 'Pengurusan Pengguna', 'description' => 'Edit existing users'],
            ['name' => 'pengguna.delete', 'module' => 'Pengurusan Pengguna', 'description' => 'Delete users'],
            ['name' => 'pengguna.manage', 'module' => 'Pengurusan Pengguna', 'description' => 'Full user management access'],
            ['name' => 'pengguna.reset-password', 'module' => 'Pengurusan Pengguna', 'description' => 'Reset user passwords'],
            ['name' => 'pengguna.toggle-status', 'module' => 'Pengurusan Pengguna', 'description' => 'Activate/deactivate users'],

            // Role Management
            ['name' => 'peranan.view', 'module' => 'Pengurusan Peranan', 'description' => 'View roles list and details'],
            ['name' => 'peranan.create', 'module' => 'Pengurusan Peranan', 'description' => 'Create new roles'],
            ['name' => 'peranan.edit', 'module' => 'Pengurusan Peranan', 'description' => 'Edit existing roles'],
            ['name' => 'peranan.delete', 'module' => 'Pengurusan Peranan', 'description' => 'Delete roles'],
            ['name' => 'peranan.manage', 'module' => 'Pengurusan Peranan', 'description' => 'Full role management access'],
            ['name' => 'peranan.assign-permissions', 'module' => 'Pengurusan Peranan', 'description' => 'Assign permissions to roles'],

            // Permission Management
            ['name' => 'capaian.view', 'module' => 'Pengurusan Capaian', 'description' => 'View permissions list and details'],
            ['name' => 'capaian.create', 'module' => 'Pengurusan Capaian', 'description' => 'Create new permissions'],
            ['name' => 'capaian.edit', 'module' => 'Pengurusan Capaian', 'description' => 'Edit existing permissions'],
            ['name' => 'capaian.delete', 'module' => 'Pengurusan Capaian', 'description' => 'Delete permissions'],
            ['name' => 'capaian.manage', 'module' => 'Pengurusan Capaian', 'description' => 'Full permission management access'],

            // Admin Management
            ['name' => 'admin.view', 'module' => 'Pengurusan Admin', 'description' => 'View admin panels and settings'],
            ['name' => 'admin.manage', 'module' => 'Pengurusan Admin', 'description' => 'Full administrative access'],

            // Dashboard
            ['name' => 'dashboard.view', 'module' => 'Dashboard', 'description' => 'View dashboard'],
            ['name' => 'dashboard.manage', 'module' => 'Dashboard', 'description' => 'Manage dashboard settings'],

            // Ticket Management (existing functionality)
            ['name' => 'log-tiket.view', 'module' => 'Log Tiket', 'description' => 'View tickets'],
            ['name' => 'log-tiket.create', 'module' => 'Log Tiket', 'description' => 'Create new tickets'],
            ['name' => 'log-tiket.edit', 'module' => 'Log Tiket', 'description' => 'Edit tickets'],
            ['name' => 'log-tiket.delete', 'module' => 'Log Tiket', 'description' => 'Delete tickets'],
            ['name' => 'log-tiket.assign', 'module' => 'Log Tiket', 'description' => 'Assign tickets to users'],
            ['name' => 'log-tiket.close', 'module' => 'Log Tiket', 'description' => 'Close/resolve tickets'],

            //knowledge base
            ['name' => 'knowledgebase.view', 'module' => 'Knowledge Base', 'description' => 'View knowledge base articles'],
            ['name' => 'knowledgebase.create', 'module' => 'Knowledge Base', 'description' => 'Create knowledge base articles'],
            ['name' => 'knowledgebase.edit', 'module' => 'Knowledge Base', 'description' => 'Edit knowledge base articles'],
            ['name' => 'knowledgebase.delete', 'module' => 'Knowledge Base', 'description' => 'Delete knowledge base articles'],

            // Configuration Management
            ['name' => 'konfigurasi.view', 'module' => 'Pengurusan Konfigurasi', 'description' => 'View system configurations'],
            ['name' => 'konfigurasi.edit', 'module' => 'Pengurusan Konfigurasi', 'description' => 'Edit system configurations'],

            // Report Management
            ['name' => 'laporan.view', 'module' => 'Pengurusan Laporan', 'description' => 'View reports'],
            ['name' => 'laporan.create', 'module' => 'Pengurusan Laporan', 'description' => 'Generate reports'],
            ['name' => 'laporan.export', 'module' => 'Pengurusan Laporan', 'description' => 'Export reports'],
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
                'name' => 'Administrator',
                'guard_name' => 'web',
                'description' => 'System administrator with full access',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pentadbir Sistem',
                'guard_name' => 'web',
                'description' => 'System administrator for agency management',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penyelia Helpdesk',
                'guard_name' => 'web',
                'description' => 'Helpdesk supervisor role',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Helpdesk On Duty',
                'guard_name' => 'web',
                'description' => 'Helpdesk staff on duty',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pegawai Tugasan',
                'guard_name' => 'web',
                'description' => 'Assignment officer role',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pengurusan MOF',
                'guard_name' => 'web',
                'description' => 'Ministry of Finance management role',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                [
                    'guard_name' => $role['guard_name'],
                    'description' => $role['description'],
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
                'nama' => 'Super Administrator',
                'email' => 'superadmin@ehelp.gov.my',
                'password' => Hash::make('password123'),
                'kodaktif' => true,
                'email_verified_at' => now(),
                'first_time_login' => false,
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
        'Administrator' => [
            // User Management
            'pengguna.view', 'pengguna.create', 'pengguna.edit', 'pengguna.delete',
            'pengguna.manage', 'pengguna.reset-password', 'pengguna.toggle-status',
            // Role Management
            'peranan.view', 'peranan.create', 'peranan.edit', 'peranan.delete',
            'peranan.manage', 'peranan.assign-permissions',
            // Permission Management
            'capaian.view', 'capaian.create', 'capaian.edit', 'capaian.delete',
            'capaian.manage',
            // Admin Management
            'admin.view', 'admin.manage',
            // Dashboard
            'dashboard.view', 'dashboard.manage',
            // Ticket Management
            'log-tiket.view', 'log-tiket.create', 'log-tiket.edit', 'log-tiket.delete',
            'log-tiket.assign', 'log-tiket.close',

            // Knowledge Base
            'knowledgebase.view', 'knowledgebase.create', 'knowledgebase.edit', 'knowledgebase.delete',
            // Configuration Management
            'konfigurasi.view', 'konfigurasi.edit',
            // Report Management
            'laporan.view', 'laporan.create', 'laporan.export',
        ],
        'Pentadbir Sistem' => [
            // User Management
            'pengguna.view', 'pengguna.create', 'pengguna.edit', 'pengguna.delete', 'pengguna.manage',
            // Role Management
            'peranan.view', 'peranan.assign-permissions',
            // Dashboard
            'dashboard.view', 'dashboard.manage',
            // Ticket Management
            'log-tiket.view', 'log-tiket.create', 'log-tiket.edit', 'log-tiket.assign',
            // Configuration Management
            'konfigurasi.view', 'konfigurasi.edit',
            // Report Management
            'laporan.view', 'laporan.create',
        ],
        'Penyelia Helpdesk' => [
            // Dashboard
            'dashboard.view',
            // Ticket Management
            'log-tiket.view', 'log-tiket.create', 'log-tiket.edit', 'log-tiket.assign', 'log-tiket.close',
            // Report Management
            'laporan.view', 'laporan.create', 'laporan.export',
        ],
        'Helpdesk On Duty' => [
            // Dashboard
            'dashboard.view',
            // Ticket Management
            'log-tiket.view', 'log-tiket.create', 'log-tiket.edit', 'log-tiket.close',
        ],
        'Pegawai Tugasan' => [
            // Dashboard
            'dashboard.view',
            // Ticket Management
            'log-tiket.view', 'log-tiket.assign',
            // Report Management
            'laporan.view',
        ],
        'Pengurusan MOF' => [
            // Dashboard
            'dashboard.view',
            // Report Management
            'laporan.view', 'laporan.export',
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
            'superadmin@ehelp.gov.my' => 'Administrator',
        ];

        foreach ($userRoles as $email => $roleName) {
            $user = User::where('email', $email)->first();
            $role = Role::where('name', $roleName)->first();

            if ($user && $role) {
                $user->syncRoles($role);
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
