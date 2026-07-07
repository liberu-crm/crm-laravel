<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\Portal\Widgets\PortalOverview;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function actingCustomer(): User
    {
        $this->seed(RolesSeeder::class);
        $customer = User::factory()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $customer->assignRole('customer');
        $this->actingAs($customer);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        return $customer;
    }

    public function test_dashboard_loads_for_customer(): void
    {
        $this->actingCustomer();

        $this->get('/portal')->assertOk();
    }

    public function test_overview_widget_shows_scoped_counts(): void
    {
        $customer = $this->actingCustomer();
        Ticket::factory()->count(3)->create(['user_id' => $customer->id, 'status' => 'open']);
        Ticket::factory()->create(); // another user's ticket — must not be counted

        Livewire::test(PortalOverview::class)
            ->assertSee('Open tickets')
            ->assertSee('Help articles')
            ->assertSee('Documents')
            ->assertSee('3');
    }
}
