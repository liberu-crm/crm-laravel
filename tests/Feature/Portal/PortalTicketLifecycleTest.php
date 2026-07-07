<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\Portal\Resources\TicketResource\Pages\ViewTicket;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalTicketLifecycleTest extends TestCase
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

    public function test_customer_closes_own_ticket(): void
    {
        $customer = $this->actingCustomer();
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => 'open']);

        Livewire::test(ViewTicket::class, ['record' => $ticket->getKey()])
            ->callAction('close');

        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_customer_reopens_closed_ticket(): void
    {
        $customer = $this->actingCustomer();
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => 'closed']);

        Livewire::test(ViewTicket::class, ['record' => $ticket->getKey()])
            ->callAction('reopen');

        $this->assertSame('open', $ticket->fresh()->status);
    }
}
