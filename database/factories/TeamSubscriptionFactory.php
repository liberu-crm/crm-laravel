<?php

namespace Database\Factories;

use App\Models\TeamSubscription;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamSubscriptionFactory extends Factory
{
    protected $model = TeamSubscription::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->word,
            'stripe_id' => 'sub_' . $this->faker->md5,
            'stripe_status' => 'active',
            'stripe_price' => 'price_' . $this->faker->md5,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}