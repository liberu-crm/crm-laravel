<?php

namespace Database\Factories;

use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        return [
            'opportunity_id' => $this->faker->unique()->numberBetween(1, 999999),
            'deal_size' => $this->faker->randomFloat(2, 1000, 500000),
            'stage' => $this->faker->randomElement(['prospect', 'proposal', 'negotiation', 'won', 'lost']),
            'closing_date' => $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
        ];
    }
}
