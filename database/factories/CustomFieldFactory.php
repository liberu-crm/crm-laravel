<?php

namespace Database\Factories;

use App\Models\CustomField;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomFieldFactory extends Factory
{
    protected $model = CustomField::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->word,
            'label' => $this->faker->words(2, true),
            'type' => $this->faker->randomElement(['text', 'number', 'date', 'select', 'checkbox']),
            'model_type' => $this->faker->randomElement(['App\\Models\\Contact', 'App\\Models\\Lead', 'App\\Models\\Deal']),
            'options' => json_encode($this->faker->randomElement([
                ['option1', 'option2', 'option3'],
                null
            ])),
            'required' => $this->faker->boolean,
            'validation_rules' => json_encode([
                'min' => $this->faker->optional()->numberBetween(1, 10),
                'max' => $this->faker->optional()->numberBetween(10, 100)
            ]),
            'order' => $this->faker->numberBetween(1, 10),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}