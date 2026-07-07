<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\Portal\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Portal\Resources\TicketResource\Pages\ViewTicket;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\CRMEventNotification;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CreatePortalTicketTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A customer whose current_team_id points at $team (the tenant they belong
     * to). $team is owned by a separate staff user so team()->allUsers() has a
     * staff recipient for the notification test.
     */
    private function actingTeamedCustomer(?Team &$team = null, ?User &$staff = null): User
    {
        $this->seed(RolesSeeder::class);
        $staff = User::factory()->create(['email_verified_at' => now()]);
        $team = Team::factory()->create(['user_id' => $staff->id]);

        $customer = User::factory()->create(['email_verified_at' => now()]);
        $customer->forceFill(['current_team_id' => $team->id])->save();
        setPermissionsTeamId(null);
        $customer->assignRole('customer');

        $this->actingAs($customer);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        return $customer;
    }

    private function actingTeamlessCustomer(): User
    {
        $this->seed(RolesSeeder::class);
        $customer = User::factory()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $customer->assignRole('customer');

        $this->actingAs($customer);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        return $customer;
    }

    public function test_teamed_customer_can_reach_create_page(): void
    {
        $this->actingTeamedCustomer();

        $this->get('/portal/tickets/create')->assertOk();
    }

    public function test_teamless_customer_cannot_create(): void
    {
        $this->actingTeamlessCustomer();

        $this->get('/portal/tickets/create')->assertForbidden();
    }

    public function test_customer_creates_ticket_with_server_set_fields_and_attachment(): void
    {
        Storage::fake('local');
        $team = null;
        $customer = $this->actingTeamedCustomer($team);
        $file = UploadedFile::fake()->create('screenshot.png', 20, 'image/png');

        Livewire::test(CreateTicket::class)
            ->fillForm([
                'subject' => 'Login broken',
                'body' => 'Cannot log in since this morning.',
                'priority' => 'high',
                'attachment' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $ticket = Ticket::withoutGlobalScope('tenant')->where('subject', 'Login broken')->first();
        $this->assertNotNull($ticket);
        $this->assertSame($customer->id, $ticket->user_id);
        $this->assertSame($team->id, $ticket->getAttribute('team_id'));
        $this->assertSame('portal', $ticket->source);
        $this->assertSame('open', $ticket->status);
        $this->assertSame('high', $ticket->priority);
        $this->assertStringStartsWith('portal-', $ticket->email_id);

        $path = $ticket->getAttribute('attachment');
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_creating_ticket_notifies_team_staff(): void
    {
        Storage::fake('local');
        Notification::fake();
        $team = null;
        $staff = null;
        $this->actingTeamedCustomer($team, $staff);

        Livewire::test(CreateTicket::class)
            ->fillForm([
                'subject' => 'Billing question',
                'body' => 'My invoice looks wrong.',
                'priority' => 'medium',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($staff, CRMEventNotification::class);
    }

    public function test_owner_can_download_their_attachment(): void
    {
        Storage::fake('local');
        $customer = $this->actingTeamlessCustomer();
        Storage::disk('local')->put('ticket-attachments/note.png', 'binary');
        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
            'attachment' => 'ticket-attachments/note.png',
        ]);

        Livewire::test(ViewTicket::class, ['record' => $ticket->getKey()])
            ->callAction('download_attachment')
            ->assertFileDownloaded('note.png');
    }
}
