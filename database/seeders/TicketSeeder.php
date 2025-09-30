<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use App\Models\RefTicketStatus;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $users      = User::pluck('id')->toArray();
        $categories = RefCategory::pluck('id')->toArray();
        $subcats    = RefSubCategory::pluck('id')->toArray();
        $statuses   = RefTicketStatus::pluck('id')->toArray();

        if (empty($users) || empty($categories) || empty($subcats) || empty($statuses)) {
            $this->command->warn('⚠️ Sila pastikan User, Category, SubCategory, dan Ticket Status telah ada sebelum run TicketSeeder.');
            return;
        }

        Ticket::factory()->count(20)->create([
            'user_id'      => fake()->randomElement($users),
            'cat_id'       => fake()->randomElement($categories),
            'subcat_id'    => fake()->randomElement($subcats),
            'tick_status'  => fake()->randomElement($statuses),
            'ticket_title' => fake()->sentence(6),
            'ticket_desc'  => fake()->paragraph(3),
            'ticket_type'  => fake()->randomElement([0, 1]), // 0 = Bagi Pihak, 1 = Diri Sendiri
            'created_by'   => fake()->randomElement($users),
            'updated_by'   => null,
        ]);
    }
}
