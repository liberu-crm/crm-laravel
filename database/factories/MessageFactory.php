<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'subject' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['direct', 'group', 'system']),
            'status' => $this->faker->randomElement(['unread', 'read', 'archived']),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high']),
            'metadata' => json_encode([
                'attachments' => [],
                'mentions' => [],
                'thread_id' => $this->faker->optional()->uuid
            ]),
            'read_at' => $this->faker->optional()->dateTimeThisMonth(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}