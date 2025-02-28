<?php

namespace Database\Factories;

use App\Models\MarketingCampaign;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketingCampaignFactory extends Factory
{
    protected $model = MarketingCampaign::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['email', 'sms', 'social_media', 'ads']),
            'status' => $this->faker->randomElement(['draft', 'scheduled', 'active', 'completed', 'paused']),
            'description' => $this->faker->paragraph,
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'budget' => $this->faker->randomFloat(2, 1000, 50000),
            'target_audience' => json_encode([
                'demographics' => [
                    'age_range' => [$this->faker->numberBetween(18, 30), $this->faker->numberBetween(31, 65)],
                    'locations' => $this->faker->words(3),
                    'interests' => $this->faker->words(5)
                ]
            ]),
            'settings' => json_encode([
                'frequency' => $this->faker->randomElement(['once', 'daily', 'weekly', 'monthly']),
                'tracking' => [
                    'utm_source' => $this->faker->word,
                    'utm_medium' => $this->faker->word,
                    'utm_campaign' => $this->faker->slug
                ]
            ]),
            'metrics' => json_encode([
                'sent' => 0,
                'opened' => 0,
                'clicked' => 0,
                'converted' => 0,
                'unsubscribed' => 0
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}