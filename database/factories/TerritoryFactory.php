<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Team;
use App\Models\Territory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TerritoryFactory extends Factory
{
    protected $model = Territory::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->unique()->city().' Region',
        ];
    }
}
