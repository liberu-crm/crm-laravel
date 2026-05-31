<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard;
use App\Models\DashboardWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_component_renders(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertStatus(200);
    }

    public function test_dashboard_loads_user_widgets(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        DashboardWidget::factory()->create([
            'user_id' => $user->id,
            'widget_type' => 'contacts',
            'position' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('widgets', fn ($widgets) => $widgets->count() === 1);
    }

    public function test_add_widget_increases_widget_count(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('addWidget', 'contacts')
            ->assertSet('widgets', fn ($widgets) => $widgets->count() === 1);
    }

    public function test_remove_widget_decreases_widget_count(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $widget = DashboardWidget::factory()->create([
            'user_id' => $user->id,
            'widget_type' => 'contacts',
            'position' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('removeWidget', $widget->id)
            ->assertSet('widgets', fn ($widgets) => $widgets->isEmpty());
    }
}
