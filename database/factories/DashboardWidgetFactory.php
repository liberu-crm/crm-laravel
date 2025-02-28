<?php

namespace Database\Factories;

use App\Models\DashboardWidget;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class DashboardWidgetFactory extends Factory
{
    protected $model = DashboardWidget::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->words(2, true),
            'type' => $this->faker->randomElement(['chart', 'list', 'metric', 'calendar']),
            'settings' => json_encode([
                'refresh_interval' => $this->faker->randomElement([null, 300, 600, 1800]),
                'chart_type' => $this->faker->randomElement(['line', 'bar', 'pie']),
                'data_source' => $this->faker->randomElement(['leads', 'deals', 'tasks']),
                'time_range' => $this->faker->randomElement(['today', 'week', 'month', 'quarter', 'year'])
            ]),
            'position' => json_encode([
                'x' => $this->faker->numberBetween(0, 3),
                'y' => $this->faker->numberBetween(0, 3),
                'width' => $this->faker->numberBetween(1, 2),
                'height' => $this->faker->numberBetween(1, 2)
            ]),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}