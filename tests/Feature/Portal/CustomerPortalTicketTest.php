<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\Portal\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Portal\Resources\TicketResource\Pages\ViewTicket;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPortalTicketTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $user->assignRole('customer');

        return $user;
    }

    private function actingCustomer(): User
    {
        $user = $this->customer();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        return $user;
    }

    public function test_customer_can_access_portal(): void
    {
        $this->actingCustomer();

        $this->get('/portal/tickets')->assertOk();
    }

    public function test_customer_cannot_access_app_or_admin(): void
    {
        $this->actingCustomer();

        $this->get('/app')->assertForbidden();
        $this->get('/admin')->assertForbidden();
    }

    public function test_staff_cannot_access_portal(): void
    {
        $this->seed(RolesSeeder::class);
        $staff = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $staff->assignRole('manager');
        $this->actingAs($staff);

        $this->get('/portal/tickets')->assertForbidden();
    }

    public function test_customer_sees_only_own_tickets(): void
    {
        $customer = $this->actingCustomer();
        $mine = Ticket::factory()->create(['user_id' => $customer->id, 'subject' => 'My issue']);
        $other = Ticket::factory()->create(['subject' => 'Someone else']);

        Livewire::test(ListTickets::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_customer_cannot_open_foreign_ticket(): void
    {
        // The view route is /portal/tickets/{record}; scoped record resolution
        // means the reply surface for a foreign ticket is a 404 (IDOR closed).
        $customer = $this->actingCustomer();
        $mine = Ticket::factory()->create(['user_id' => $customer->id]);
        $foreign = Ticket::factory()->create();

        $this->get('/portal/tickets/'.$mine->id)->assertOk();
        $this->get('/portal/tickets/'.$foreign->id)->assertNotFound();
    }

    public function test_reply_creates_scoped_message_on_own_ticket(): void
    {
        $team = Team::factory()->create();
        $customer = $this->actingCustomer();
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'team_id' => $team->id]);

        Livewire::test(ViewTicket::class, ['record' => $ticket->getKey()])
            ->callAction('reply', ['content' => 'Any update?']);

        $this->assertDatabaseHas('messages', [
            'ticket_id' => $ticket->id,
            'channel' => 'portal',
            'sender' => $customer->email,
            'team_id' => $team->id,
            'content' => 'Any update?',
        ]);
    }
}
