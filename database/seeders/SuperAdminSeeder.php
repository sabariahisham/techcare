<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create basic permissions if none exist
        $this->createBasicPermissions();

        // Create Administrator role if it doesn't exist
        $adminRole = Role::firstOrCreate([
            'name' => 'Administrator',
            'guard_name' => 'web'
        ], [
            'description' => 'Super Administrator with full system access',
            'color' => '#dc2626',
            'icon' => 'bx-crown',
            'is_active' => true
        ]);

        // Get all permissions
        $allPermissions = Permission::all();

        // Give all permissions to Administrator role
        $adminRole->syncPermissions($allPermissions);

        echo "Administrator role created/updated with " . $allPermissions->count() . " permissions.\n";

        // Find the first user or create one if none exists
        $user = User::first();

        if (!$user) {
            $user = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'password' => bcrypt('password123'),
                'is_active' => true
            ]);
            echo "Created new admin user: admin@example.com (password: password123)\n";
        }

        // Assign Administrator role to the user
        $user->syncRoles(['Administrator']);

        echo "Administrator role assigned to user: " . $user->name . " (" . $user->email . ")\n";

        // Clear cache again
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Create basic permissions if none exist.
     */
    private function createBasicPermissions(): void
    {
        $basicPermissions = [
            // Dashboard permissions
            ['name' => 'dashboard.view', 'module' => 'Dashboard', 'description' => 'View dashboard'],
            ['name' => 'dashboard.create', 'module' => 'Dashboard', 'description' => 'Create dashboard items'],
            ['name' => 'dashboard.edit', 'module' => 'Dashboard', 'description' => 'Edit dashboard items'],
            ['name' => 'dashboard.delete', 'module' => 'Dashboard', 'description' => 'Delete dashboard items'],

            // User management permissions
            ['name' => 'users.view', 'module' => 'User Management', 'description' => 'View users'],
            ['name' => 'users.create', 'module' => 'User Management', 'description' => 'Create users'],
            ['name' => 'users.edit', 'module' => 'User Management', 'description' => 'Edit users'],
            ['name' => 'users.delete', 'module' => 'User Management', 'description' => 'Delete users'],

            // Role management permissions
            ['name' => 'roles.view', 'module' => 'Role Management', 'description' => 'View roles'],
            ['name' => 'roles.create', 'module' => 'Role Management', 'description' => 'Create roles'],
            ['name' => 'roles.edit', 'module' => 'Role Management', 'description' => 'Edit roles'],
            ['name' => 'roles.delete', 'module' => 'Role Management', 'description' => 'Delete roles'],

            // Permission management permissions
            ['name' => 'permissions.view', 'module' => 'Permission Management', 'description' => 'View permissions'],
            ['name' => 'permissions.create', 'module' => 'Permission Management', 'description' => 'Create permissions'],
            ['name' => 'permissions.edit', 'module' => 'Permission Management', 'description' => 'Edit permissions'],
            ['name' => 'permissions.delete', 'module' => 'Permission Management', 'description' => 'Delete permissions'],

            // System settings permissions
            ['name' => 'settings.view', 'module' => 'System Settings', 'description' => 'View system settings'],
            ['name' => 'settings.edit', 'module' => 'System Settings', 'description' => 'Edit system settings'],
        ];

        foreach ($basicPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => 'web'],
                $permissionData
            );
        }

        echo "Basic permissions created/verified.\n";
    }
}
