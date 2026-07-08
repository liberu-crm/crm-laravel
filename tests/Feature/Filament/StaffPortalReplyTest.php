<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Actions\Portal\ReplyToPortalTicket;
use App\Filament\App\Resources\TicketResource\Pages\ListTickets;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use App\Services\UnifiedHelpDeskService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class StaffPortalReplyTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->staff = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $this->team = $this->staff->currentTeam;
        setPermissionsTeamId($this->team->id);
        $this->staff->assignRole('manager');
        $this->actingAs($this->staff);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($this->team);
    }

    private function portalTicket(User $customer): Ticket
    {
        return Ticket::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $customer->id,
            'source' => 'portal',
            'account_id' => 0,
        ]);
    }

    public function test_staff_reply_to_portal_ticket_persists_a_scoped_message(): void
    {
        Notification::fake();
        $customer = User::factory()->create();
        $ticket = $this->portalTicket($customer);

        Livewire::test(ListTickets::class)
            ->callTableAction('reply', $ticket, data: ['reply_content' => 'Here is the fix.']);

        $this->assertDatabaseHas('messages', [
            'ticket_id' => $ticket->id,
            'channel' => 'portal',
            'sender' => $this->staff->name,
            'team_id' => $this->team->id,
            'content' => 'Here is the fix.',
        ]);
        $this->assertSame('in_progress', $ticket->fresh()->getAttribute('status'));
    }

    public function test_staff_reply_notifies_the_customer(): void
    {
        Notification::fake();
        $customer = User::factory()->create();
        $ticket = $this->portalTicket($customer);

        Livewire::test(ListTickets::class)
            ->callTableAction('reply', $ticket, data: ['reply_content' => 'Answered.']);

        Notification::assertSentTo($customer, TicketReplyNotification::class);
    }

    public function test_staff_reply_to_portal_ticket_does_not_call_external_service(): void
    {
        Notification::fake();
        $customer = User::factory()->create();
        $ticket = $this->portalTicket($customer);

        $this->mock(UnifiedHelpDeskService::class, function ($mock): void {
            $mock->shouldNotReceive('sendReply');
        });

        Livewire::test(ListTickets::class)
            ->callTableAction('reply', $ticket, data: ['reply_content' => 'No external call.']);

        $this->assertDatabaseHas('messages', ['ticket_id' => $ticket->id, 'channel' => 'portal']);
    }

    public function test_staff_reply_to_non_portal_ticket_routes_to_external_service(): void
    {
        Notification::fake();
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $customer->id,
            'source' => 'gmail',
            'source_id' => 'gmail-msg-1',
            'account_id' => 7,
        ]);

        $this->mock(UnifiedHelpDeskService::class, function ($mock): void {
            $mock->shouldReceive('sendReply')->once();
        });

        Livewire::test(ListTickets::class)
            ->callTableAction('reply', $ticket, data: ['reply_content' => 'Emailed back.']);

        // External path persists nothing locally and notifies nobody in-portal.
        $this->assertDatabaseMissing('messages', ['ticket_id' => $ticket->id, 'channel' => 'portal']);
        Notification::assertNothingSent();
    }

    public function test_action_persists_message_and_notifies(): void
    {
        Notification::fake();
        $customer = User::factory()->create();
        $ticket = $this->portalTicket($customer);

        $message = (new ReplyToPortalTicket)($ticket, 'Direct invoke.', $this->staff);

        $this->assertSame('portal', $message->getAttribute('channel'));
        $this->assertSame($this->team->id, $message->getAttribute('team_id'));
        $this->assertDatabaseHas('messages', [
            'ticket_id' => $ticket->id,
            'content' => 'Direct invoke.',
            'sender' => $this->staff->name,
        ]);
        Notification::assertSentTo($customer, TicketReplyNotification::class);
    }
}
