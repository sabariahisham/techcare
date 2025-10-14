<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Database\Seeders\TicketSeeder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    // Clear cached roles/permissions first
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    // First run permission and role seeders (BEFORE creating users)
    $this->call([
      EnhancedUserRolePermissionSeeder::class,
      // RoleSeeder::class,
      // PermissionSeeder::class,

      // UpdateRolesSeeder::class,
      // SuperAdminSeeder::class,
    ]);

    // Create users AFTER roles exist
    // User::factory(25)->create();

    // Then run reference data seeders
    // $this->call([
    //   RefCategorySeeder::class,
    //   RefSubCategorySeeder::class,
    //   RefTicketStatusSeeder::class,
    // ]);

    // Finally run data seeders that depend on users and reference data
    // $this->call([
    //   TicketSeeder::class,
    // ]);

    // $this->call([
    //   ModuleListSeeder::class,
    // ]);

    // Module::factory(3)->create();

    // User::factory()->create([
    //   'name' => 'Test User',
    //   'email' => 'test@example.com',
    // ]);
  }
}
