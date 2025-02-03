<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['name' => 'admin-management.view', 'module' => 'Admin Management']);
        Permission::create(['name' => 'admin-management.create', 'module' => 'Admin Management']);
        Permission::create(['name' => 'admin-management.update', 'module' => 'Admin Management']);
        Permission::create(['name' => 'admin-management.delete', 'module' => 'Admin Management']);

        Permission::create(['name' => 'user-management.view', 'module' => 'User Management']);
        Permission::create(['name' => 'user-management.create', 'module' => 'User Management']);
        Permission::create(['name' => 'user-management.update', 'module' => 'User Management']);
        Permission::create(['name' => 'user-management.delete', 'module' => 'User Management']);

        Permission::create(['name' => 'report-management.view', 'module' => 'Report Management']);
        Permission::create(['name' => 'report-management.create', 'module' => 'Report Management']);
        Permission::create(['name' => 'report-management.update', 'module' => 'Report Management']);
        Permission::create(['name' => 'report-management.delete', 'module' => 'Report Management']);

        Permission::create(['name' => 'role-management.view', 'module' => 'Role Management']);
        Permission::create(['name' => 'role-management.create', 'module' => 'Role Management']);
        Permission::create(['name' => 'role-management.update', 'module' => 'Role Management']);
        Permission::create(['name' => 'role-management.delete', 'module' => 'Role Management']);

        $role = Role::create(['name' => 'Superadmin']);
        $role->givePermissionTo(Permission::all());

        $penyediaAgensi = Role::create(['name' => 'Penyedia Agensi'])
            ->givePermissionTo(['role-management.delete']);

        $pentadbirAgensi = Role::create(['name' => 'Pentadbir Agensi'])
            ->givePermissionTo(['role-management.delete']);

        $penyemakAgensi = Role::create(['name' => 'Penyemak Agensi'])
            ->givePermissionTo(['report-management.delete']);

        $pengesahAgensi = Role::create(['name' => 'Pengesah Agensi'])
            ->givePermissionTo(['admin-management.delete']);

        $pentadbirMof = Role::create(['name' => 'Pentadbir MOF'])
            ->givePermissionTo(['role-management.delete']);

        $penyemakMof = Role::create(['name' => 'Penyemak MOF'])
            ->givePermissionTo(['role-management.delete']);

        $pengesahMof = Role::create(['name' => 'Pengesah MOF'])
            ->givePermissionTo(['role-management.delete']);
    }
}
