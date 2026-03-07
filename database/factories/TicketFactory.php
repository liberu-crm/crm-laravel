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
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['open', 'pending', 'closed']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'source' => $this->faker->randomElement(['email', 'whatsapp', 'phone', 'web']),
        ];
    }
}
