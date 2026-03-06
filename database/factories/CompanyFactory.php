<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->company,
            'industry'     => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing']),
            'website'      => $this->faker->url,
            'phone_number' => $this->faker->phoneNumber,
            'address'      => $this->faker->streetAddress,
            'city'         => $this->faker->city,
            'state'        => $this->faker->state,
            'zip'          => $this->faker->postcode,
            'description'  => $this->faker->paragraph,
        ];
    }
}
