<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Message;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'channel' => $this->faker->randomElement(['email', 'sms', 'whatsapp', 'facebook', 'chat']),
            'sender' => $this->faker->safeEmail(),
            'content' => $this->faker->paragraph(),
            'timestamp' => now(),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high']),
            'status' => $this->faker->randomElement(['unread', 'read', 'archived']),
            'account_id' => $this->faker->numberBetween(1, 1000),
            'thread_id' => null,
            'metadata' => ['attachments' => [], 'mentions' => []],
        ];
    }
}
