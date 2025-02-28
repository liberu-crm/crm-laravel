<?php

namespace Database\Factories;

use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'triggers' => json_encode([
                'type' => 'event',
                'event' => $this->faker->randomElement(['lead.created', 'contact.updated', 'deal.closed'])
            ]),
            'actions' => json_encode([
                [
                    'type' => 'notification',
                    'channel' => 'email',
                    'template' => 'default'
                ]
            ]),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}