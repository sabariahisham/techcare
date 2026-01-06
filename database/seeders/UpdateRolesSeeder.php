<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear the roles table with foreign key constraints consideration
        $this->command->info('Clearing roles table...');

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear model_has_roles and role_has_permissions first
        DB::table('model_has_roles')->delete();
        DB::table('role_has_permissions')->delete();

        // Then clear roles table
        DB::table('roles')->delete();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create updated roles with new names
        $this->command->info('Creating updated roles...');

        $roles = [
            [
                'name' => 'Administrator',
                'guard_name' => 'web',
                'description' => 'System administrator with full access',
                'color' => 'danger',
                'icon' => 'bx-crown',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pentadbir Sistem',
                'guard_name' => 'web',
                'description' => 'System administrator for agency management',
                'color' => 'warning',
                'icon' => 'bx-cog',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penyelia Helpdesk',
                'guard_name' => 'web',
                'description' => 'Helpdesk supervisor role',
                'color' => 'primary',
                'icon' => 'bx-user-check',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Helpdesk On Duty',
                'guard_name' => 'web',
                'description' => 'Helpdesk staff on duty',
                'color' => 'info',
                'icon' => 'bx-headphone',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pegawai Tugasan',
                'guard_name' => 'web',
                'description' => 'Assignment officer role',
                'color' => 'success',
                'icon' => 'bx-task',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pengurusan MOF',
                'guard_name' => 'web',
                'description' => 'Ministry of Finance management role',
                'color' => 'secondary',
                'icon' => 'bx-building',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert the new roles
        foreach ($roles as $role) {
            Role::create($role);
            $this->command->info("Created role: {$role['name']}");
        }

        $this->command->info('Roles table updated successfully!');
    }
}
