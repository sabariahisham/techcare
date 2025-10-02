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
    $this->call([
      RefCategorySeeder::class,
      RefSubCategorySeeder::class,
      RefTicketStatusSeeder::class,
      TicketSeeder::class,
    ]);

    // $this->call([
    //   ModuleListSeeder::class,
    //   RolesAndPermissionSeeder::class
    // ]);

    // User::factory(50)->create();

    // Module::factory(3)->create();

    // User::factory()->create([
    //   'name' => 'Test User',
    //   'email' => 'test@example.com',
    // ]);
  }
}
