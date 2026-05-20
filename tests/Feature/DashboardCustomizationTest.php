<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DashboardWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_widget()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('dashboard')
            ->call('addWidget', 'stats')
            ->assertCount('widgets', 1);

        $this->assertDatabaseHas('dashboard_widgets', [
            'user_id' => $user->id,
            'widget_type' => 'stats',
        ]);
    }

    public function test_user_can_remove_widget()
    {
        $user = User::factory()->create();
        $widget = DashboardWidget::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('dashboard')
            ->call('removeWidget', $widget->id)
            ->assertCount('widgets', 0);

        $this->assertDatabaseMissing('dashboard_widgets', ['id' => $widget->id]);
    }

    public function test_user_can_reorder_widgets()
    {
        $user = User::factory()->create();
        $widget1 = DashboardWidget::factory()->create(['user_id' => $user->id, 'position' => 1]);
        $widget2 = DashboardWidget::factory()->create(['user_id' => $user->id, 'position' => 2]);

        Livewire::actingAs($user)
            ->test('dashboard')
            ->call('updateWidgetOrder', [$widget2->id, $widget1->id]);

        $this->assertEquals(1, $widget2->fresh()->position);
        $this->assertEquals(2, $widget1->fresh()->position);
    }
}