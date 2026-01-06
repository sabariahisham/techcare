<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ticket;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use App\Models\RefTicketStatus;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TicketSeeder extends Seeder
{
  public function run(): void
  {
    $users      = User::pluck('id')->toArray();
    $categories = RefCategory::pluck('id')->toArray();
    $subcats    = RefSubCategory::pluck('id')->toArray();
    $statuses   = RefTicketStatus::pluck('id')->toArray();

    if (empty($users)) {
      $this->command->warn('⚠️ No users found. Creating a default user...');
      $user = User::create([
        'name' => 'Default User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
      ]);

      // Assign role if it exists
      if (Role::where('name', 'Pengadu')->exists()) {
        $user->assignRole('Pengadu');
      }

      $users = [$user->id];
    }

    if (empty($categories) || empty($subcats) || empty($statuses)) {
      $this->command->warn('⚠️ Sila pastikan Category, SubCategory, dan Ticket Status telah ada sebelum run TicketSeeder.');
      return;
    }

    // Create 20 sample tickets using factory
    Ticket::factory()->count(20)->create([
      'user_id' => fake()->randomElement($users),
      'cat_id' => fake()->randomElement($categories),
      'subcat_id' => fake()->randomElement($subcats),
      'tick_status' => fake()->randomElement($statuses),
      'created_by' => fake()->randomElement($users),
    ]);

    $this->command->info('✅ 20 sample tickets telah berjaya dicipta.');
  }
}
