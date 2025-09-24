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
        Permission::firstOrCreate(['name' => 'log-tiket.view', 'module' => 'Log Tiket']);
        Permission::firstOrCreate(['name' => 'log-tiket.create', 'module' => 'Log Tiket']);
        Permission::firstOrCreate(['name' => 'log-tiket.edit', 'module' => 'Log Tiket']);
        Permission::firstOrCreate(['name' => 'log-tiket.delete', 'module' => 'Log Tiket']);

        Permission::firstOrCreate(['name' => 'dashboard.view', 'module' => 'Dashboard']);
        Permission::firstOrCreate(['name' => 'dashboard.create', 'module' => 'Dashboard']);
        Permission::firstOrCreate(['name' => 'dashboard.edit', 'module' => 'Dashboard']);
        Permission::firstOrCreate(['name' => 'dashboard.delete', 'module' => 'Dashboard']);

        Permission::firstOrCreate(['name' => 'knowledge-base.view', 'module' => 'Knowledge Base']);
        Permission::firstOrCreate(['name' => 'knowledge-base.create', 'module' => 'Knowledge Base']);
        Permission::firstOrCreate(['name' => 'knowledge-base.edit', 'module' => 'Knowledge Base']);
        Permission::firstOrCreate(['name' => 'knowledge-base.delete', 'module' => 'Knowledge Base']);

        Permission::firstOrCreate(['name' => 'laporan.view', 'module' => 'Laporan']);
        Permission::firstOrCreate(['name' => 'laporan.create', 'module' => 'Laporan']);
        Permission::firstOrCreate(['name' => 'laporan.edit', 'module' => 'Laporan']);
        Permission::firstOrCreate(['name' => 'laporan.delete', 'module' => 'Laporan']);

        Permission::firstOrCreate(['name' => 'pentadbir.view', 'module' => 'Pentadbir']);
        Permission::firstOrCreate(['name' => 'pentadbir.create', 'module' => 'Pentadbir']);
        Permission::firstOrCreate(['name' => 'pentadbir.edit', 'module' => 'Pentadbir']);
        Permission::firstOrCreate(['name' => 'pentadbir.delete', 'module' => 'Pentadbir']);

        // $role = Role::create(['name' => 'Superadmin']);
        // $role->givePermissionTo(Permission::all());

        // $penyediaAgensi = Role::create(['name' => 'Penyedia Agensi'])
        //     ->givePermissionTo(['role-management.delete']);

        // $pentadbirAgensi = Role::create(['name' => 'Pentadbir Agensi'])
        //     ->givePermissionTo(['role-management.delete']);

        // $penyemakAgensi = Role::create(['name' => 'Penyemak Agensi'])
        //     ->givePermissionTo(['report-management.delete']);

        // $pengesahAgensi = Role::create(['name' => 'Pengesah Agensi'])
        //     ->givePermissionTo(['admin-management.delete']);

        // $pentadbirMof = Role::create(['name' => 'Pentadbir MOF'])
        //     ->givePermissionTo(['role-management.delete']);

        // $penyemakMof = Role::create(['name' => 'Penyemak MOF'])
        //     ->givePermissionTo(['role-management.delete']);

        // $pengesahMof = Role::create(['name' => 'Pengesah MOF'])
        //     ->givePermissionTo(['role-management.delete']);
    }
}
