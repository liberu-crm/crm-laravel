<?php

namespace Tests\Unit;

use App\Models\DashboardWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_widget_belongs_to_user()
    {
        $user = User::factory()->create();
        $widget = DashboardWidget::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $widget->user);
        $this->assertEquals($user->id, $widget->user->id);
    }

    public function test_dashboard_widget_has_correct_fillable_attributes()
    {
        $widget = new DashboardWidget();

        $this->assertEquals([
            'user_id',
            'widget_type',
            'position',
            'settings',
        ], $widget->getFillable());
    }

    public function test_dashboard_widget_casts_settings_to_array()
    {
        $widget = DashboardWidget::factory()->create([
            'settings' => ['key' => 'value'],
        ]);

        $this->assertIsArray($widget->settings);
        $this->assertEquals(['key' => 'value'], $widget->settings);
    }
}