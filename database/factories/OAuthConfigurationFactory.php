<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OAuthConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class OAuthConfigurationFactory extends Factory
{
    protected $model = OAuthConfiguration::class;

    public function definition(): array
    {
        // NOT NULL: service_name, client_id, client_secret. `user_id` is in
        // the model's $fillable but has no column — omitted (drift, flagged).
        return [
            'service_name' => $this->faker->unique()->randomElement(['google', 'microsoft', 'twilio', 'facebook', 'linkedin']),
            'account_name' => $this->faker->company(),
            'client_id' => $this->faker->uuid(),
            'client_secret' => $this->faker->sha256(),
            'additional_settings' => [],
            'is_active' => true,
        ];
    }
}
