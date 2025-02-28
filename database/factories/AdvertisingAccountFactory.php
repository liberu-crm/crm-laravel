<?php

namespace Database\Factories;

use App\Models\AdvertisingAccount;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdvertisingAccountFactory extends Factory
{
    protected $model = AdvertisingAccount::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->company,
            'platform' => $this->faker->randomElement(['facebook', 'google', 'linkedin']),
            'account_id' => $this->faker->numerify('act_##########'),
            'status' => $this->faker->randomElement(['active', 'paused', 'disabled']),
            'settings' => json_encode([
                'currency' => $this->faker->currencyCode,
                'timezone' => $this->faker->timezone,
                'daily_budget' => $this->faker->randomFloat(2, 10, 1000)
            ]),
            'access_token' => $this->faker->sha256,
            'refresh_token' => $this->faker->sha256,
            'token_expires_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}