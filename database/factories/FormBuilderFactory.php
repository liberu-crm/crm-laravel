<?php

declare(strict_types=1);

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
            'fields' => [
                [
                    'type' => 'text',
                    'label' => 'Full Name',
                    'name' => 'full_name',
                    'required' => true,
                ],
                [
                    'type' => 'email',
                    'label' => 'Email Address',
                    'name' => 'email',
                    'required' => true,
                ],
            ],
        ];
    }
}
