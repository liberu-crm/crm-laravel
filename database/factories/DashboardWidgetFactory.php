<?php

namespace Database\Factories;

use App\Models\DashboardWidget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DashboardWidgetFactory extends Factory
{
    protected $model = DashboardWidget::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'widget_type' => $this->faker->randomElement(['chart', 'list', 'metric', 'calendar']),
            'position'    => $this->faker->numberBetween(0, 10),
            'settings'    => [
                'refresh_interval' => $this->faker->randomElement([null, 300, 600, 1800]),
                'chart_type'       => $this->faker->randomElement(['line', 'bar', 'pie']),
                'data_source'      => $this->faker->randomElement(['leads', 'deals', 'tasks']),
                'time_range'       => $this->faker->randomElement(['today', 'week', 'month', 'quarter', 'year']),
            ],
        ];
    }
}
