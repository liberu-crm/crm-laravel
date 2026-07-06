<?php

declare(strict_types=1);

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
            'type' => $this->faker->randomElement(['email', 'sms', 'whatsapp']),
            'status' => $this->faker->randomElement(['draft', 'scheduled', 'sent', 'cancelled']),
            'subject' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(),
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ];
    }
}
