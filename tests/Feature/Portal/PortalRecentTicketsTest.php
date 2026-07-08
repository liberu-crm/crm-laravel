<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\Portal\Widgets\RecentTickets;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalRecentTicketsTest extends TestCase
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

    public function test_widget_lists_only_the_customers_recent_tickets(): void
    {
        $customer = $this->actingCustomer();
        $mine = Ticket::factory()->create(['user_id' => $customer->id, 'subject' => 'My recent issue']);
        $foreign = Ticket::factory()->create(['subject' => 'Not mine']);

        Livewire::test(RecentTickets::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_widget_is_registered_on_the_portal_panel(): void
    {
        $this->assertContains(RecentTickets::class, Filament::getPanel('portal')->getWidgets());
    }
}
