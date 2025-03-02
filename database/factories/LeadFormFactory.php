<?php

namespace Database\Factories;

use App\Models\LeadForm;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFormFactory extends Factory
{
    protected $model = LeadForm::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'fields' => json_encode([
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Full Name',
                    'required' => true
                ],
                [
                    'type' => 'email',
                    'name' => 'email',
                    'label' => 'Email Address',
                    'required' => true
                ],
                [
                    'type' => 'phone',
                    'name' => 'phone',
                    'label' => 'Phone Number',
                    'required' => false
                ]
            ]),
            'settings' => json_encode([
                'submit_button_text' => 'Submit',
                'success_message' => 'Thank you for your interest!',
                'notification_email' => $this->faker->safeEmail,
                'redirect_url' => $this->faker->url
            ]),
            'style' => json_encode([
                'theme' => $this->faker->randomElement(['light', 'dark']),
                'primary_color' => $this->faker->hexColor,
                'font_family' => 'Arial, sans-serif'
            ]),
            'status' => $this->faker->randomElement(['active', 'inactive', 'draft']),
            'conversion_rate' => $this->faker->randomFloat(2, 0, 100),
            'views' => $this->faker->numberBetween(0, 10000),
            'submissions' => $this->faker->numberBetween(0, 1000),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}