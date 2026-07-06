<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SocialMediaPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialMediaPostFactory extends Factory
{
    protected $model = SocialMediaPost::class;

    public function definition(): array
    {
        // NOT NULL without a default: content, platforms (json), status.
        return [
            'content' => $this->faker->sentence(),
            'platforms' => $this->faker->randomElements(['facebook', 'twitter', 'linkedin', 'instagram'], 2),
            'status' => SocialMediaPost::STATUS_DRAFT,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
        ];
    }
}
