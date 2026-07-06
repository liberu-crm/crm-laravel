<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AdvertisingAccount;
use App\Models\Campaign;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'advertising_account_id' => AdvertisingAccount::factory(),
            'name' => $this->faker->words(3, true),
            'external_id' => $this->faker->numerify('camp_########'),
            'status' => $this->faker->randomElement(['active', 'paused', 'archived', 'deleted']),
            'objective' => $this->faker->randomElement(['awareness', 'consideration', 'conversion']),
            'budget' => $this->faker->randomFloat(2, 100, 10000),
            'budget_type' => $this->faker->randomElement(['daily', 'lifetime']),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'metadata' => [],
        ];
    }
}
