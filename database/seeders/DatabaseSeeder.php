<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Database\Seeders\TicketSeeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    // First run reference data seeders
    $this->call([
      RefCategorySeeder::class,
      RefSubCategorySeeder::class,
      RefTicketStatusSeeder::class,
    ]);

    // Then run permission and role seeders
    $this->call([
      RolesAndPermissionSeeder::class,
      UpdateRolesSeeder::class,
      SuperAdminSeeder::class,
    ]);

    // Finally run data seeders that might depend on roles/permissions
    $this->call([
      TicketSeeder::class,
    ]);

    // $this->call([
    //   ModuleListSeeder::class,
    // ]);

    // User::factory(50)->create();

    // Module::factory(3)->create();

    // User::factory()->create([
    //   'name' => 'Test User',
    //   'email' => 'test@example.com',
    // ]);
  }
}
