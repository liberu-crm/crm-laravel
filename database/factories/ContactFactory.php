<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition()
    {
        return [
            'name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->phoneNumber,
            'status' => $this->faker->randomElement(['active', 'inactive', 'lead', 'prospect']),
            'source' => $this->faker->randomElement(['website', 'referral', 'social_media', 'direct', 'other']),
            'industry' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Education', 'Retail']),
            'lifecycle_stage' => $this->faker->randomElement(['subscriber', 'lead', 'opportunity', 'customer']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}