<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'company' => $this->faker->company,
            'status' => $this->faker->randomElement(['new', 'contacted', 'qualified', 'lost']),
            'source' => $this->faker->randomElement(['website', 'referral', 'social_media']),
            'notes' => $this->faker->paragraph,
            'created_at' => $this->faker->dateTimeThisYear(),
            'updated_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}