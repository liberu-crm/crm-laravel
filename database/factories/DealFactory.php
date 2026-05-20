<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->catchPhrase,
            'value'       => $this->faker->randomFloat(2, 1000, 1000000),
            'stage'       => $this->faker->randomElement(['prospect', 'proposal', 'negotiation', 'won', 'lost']),
            'close_date'  => $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'probability' => $this->faker->numberBetween(0, 100),
        ];
    }
}
