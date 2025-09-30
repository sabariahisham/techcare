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
            'user_id'    => 1, // boleh override masa seeding
            'cat_id'     => 1,
            'doc_id'     => null,
            'subcat_id'  => 1,
            'tick_status'=> 1,
            'tick_duedate' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'created_by' => 1,
            'updated_by' => null,
        ];
    }
}
