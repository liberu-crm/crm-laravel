<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Models\Message;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTicketThreadTest extends TestCase
{
    use RefreshDatabase;

    private function message(Ticket $ticket, string $content, string $sender): void
    {
        $message = new Message([
            'channel' => 'portal',
            'sender' => $sender,
            'content' => $content,
            'priority' => 'medium',
            'status' => 'unread',
            'account_id' => 0,
            'metadata' => [],
            'timestamp' => now(),
            'ticket_id' => $ticket->id,
        ]);
        $message->setAttribute('team_id', $ticket->getAttribute('team_id'));
        $message->save();
    }

    public function test_customer_sees_conversation_thread_on_view(): void
    {
        $this->seed(RolesSeeder::class);
        $customer = User::factory()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $customer->assignRole('customer');
        $this->actingAs($customer);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        $team = Team::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'team_id' => $team->id]);
        $this->message($ticket, 'The customer asked this.', $customer->email);
        $this->message($ticket, 'The support agent answered that.', 'Support Agent');

        $this->get('/portal/tickets/'.$ticket->id)
            ->assertOk()
            ->assertSee('The customer asked this.')
            ->assertSee('The support agent answered that.');
    }
}
