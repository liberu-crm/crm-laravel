<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'type'             => $this->faker->randomElement(['created', 'updated', 'deleted', 'commented']),
            'date'             => $this->faker->dateTimeThisYear(),
            'description'      => $this->faker->sentence(),
            'outcome'          => $this->faker->optional()->sentence(),
            'activitable_id'   => 1,
            'activitable_type' => 'App\Models\Contact',
        ];
    }
}
