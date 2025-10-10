<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class AdminRoleSeeder extends Seeder
{
    public function run()
    {
        // Create basic permissions that match menu structure
        $permissions = [
            'dashboard.view',
            'log-tiket.view',
            'knowledge-base.view',
            'laporan.view',
            'pentadbir.view',
            'user-management.view',
            'acl-roles.view',
            'acl-permissions.view',
            'module-list.view',
            'ref-category-management.view',
            'ref-sub-category-management.view',
            'sla-severity.view',
            'officer.view',
            'admin-management.view',
            'role-management.view',
            'permission-management.view',
            'configuration.view',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Administrator role
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        
        // Give all permissions to Administrator role
        $adminRole->syncPermissions($permissions);

        // Assign Administrator role to first user (or create a test user)
        $user = User::first();
        if ($user) {
            $user->assignRole('Administrator');
            echo "✓ User '{$user->nama}' assigned Administrator role\n";
        } else {
            echo "⚠ No users found in database\n";
        }

        echo "✓ Admin role and permissions created successfully\n";
    }
}