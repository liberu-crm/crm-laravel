<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ad;
use App\Models\AdSet;
use App\Models\AdvertisingAccount;
use App\Models\Campaign;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ad>
 */
class AdFactory extends Factory
{
    protected $model = Ad::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'advertising_account_id' => AdvertisingAccount::factory(),
            'campaign_id' => Campaign::factory(),
            'ad_set_id' => AdSet::factory(),
            'name' => $this->faker->words(3, true),
            'external_id' => $this->faker->numerify('ad_########'),
            'status' => $this->faker->randomElement(['active', 'paused', 'archived', 'deleted']),
            'headline' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'destination_url' => $this->faker->url(),
            'creative_url' => $this->faker->imageUrl(),
            'metadata' => [],
        ];
    }
}
