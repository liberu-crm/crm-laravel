<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activation>
 *
 * Note: the `activations` table has no `team_id` column (unlike its siblings),
 * so this factory intentionally sets none.
 */
class ActivationFactory extends Factory
{
    protected $model = Activation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => $this->faker->sha256(),
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
