<?php

namespace Database\Factories;

use App\Models\FormBuilder;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormBuilderFactory extends Factory
{
    protected $model = FormBuilder::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'fields' => json_encode([
                [
                    'type' => 'text',
                    'label' => 'Full Name',
                    'required' => true,
                    'placeholder' => 'Enter your full name'
                ],
                [
                    'type' => 'email',
                    'label' => 'Email Address',
                    'required' => true,
                    'placeholder' => 'Enter your email'
                ],
                [
                    'type' => 'select',
                    'label' => 'Interest',
                    'options' => ['Product A', 'Product B', 'Service X'],
                    'required' => false
                ]
            ]),
            'settings' => json_encode([
                'submit_button_text' => 'Submit Form',
                'success_message' => 'Thank you for your submission!',
                'redirect_url' => $this->faker->url,
                'email_notification' => true
            ]),
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}