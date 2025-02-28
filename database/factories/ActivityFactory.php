<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'team_id' => Team::factory(),
            'subject_type' => $this->faker->randomElement(['App\\Models\\Lead', 'App\\Models\\Contact', 'App\\Models\\Deal']),
            'subject_id' => $this->faker->numberBetween(1, 100),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['created', 'updated', 'deleted', 'commented']),
            'properties' => json_encode([
                'old' => [],
                'new' => [],
                'changes' => $this->faker->words(3, true)
            ]),
            'created_at' => $this->faker->dateTimeThisYear(),
            'updated_at' => $this->faker->dateTimeThisYear()
        ];
    }
}