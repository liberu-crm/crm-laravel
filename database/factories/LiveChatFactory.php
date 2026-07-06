<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LiveChat;
use Illuminate\Database\Eloquent\Factories\Factory;

class LiveChatFactory extends Factory
{
    protected $model = LiveChat::class;

    public function definition(): array
    {
        return [
            'visitor_id' => uniqid('visitor_'),
            'status' => LiveChat::STATUS_WAITING,
            'visitor_name' => $this->faker->name(),
            'visitor_email' => $this->faker->unique()->safeEmail(),
            'started_at' => now(),
        ];
    }
}
