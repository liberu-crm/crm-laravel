<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'user_id' => User::factory(),
            'personal_team' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}