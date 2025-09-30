<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
  protected $model = Ticket::class;

  public function definition(): array
  {
    return [
      'uuid' => $this->faker->uuid(),
      'ticket_title' => $this->faker->sentence(6),
      'ticket_desc'  => $this->faker->paragraph(3),
      'ticket_type'  => $this->faker->randomElement([0, 1]), // 0 = Bagi Pihak, 1 = Diri Sendiri
      'user_id' => 1,
      'cat_id' => 1,
      'doc_id' => null,
      'subcat_id' => 1,
      'tick_status' => 1,
      'tick_duedate' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
      'created_by' => 1,
      'updated_by' => null,
    ];
  }
}
