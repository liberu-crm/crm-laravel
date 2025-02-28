<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->company,
            'industry' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing']),
            'employees' => $this->faker->numberBetween(1, 10000),
            'website' => $this->faker->url,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->companyEmail,
            'address' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'postal_code' => $this->faker->postcode,
            'country' => $this->faker->country,
            'annual_revenue' => $this->faker->randomFloat(2, 10000, 10000000),
            'description' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['active', 'inactive', 'prospect']),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}