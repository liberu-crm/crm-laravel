<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\TicketResource\Pages\ListTickets;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StaffTicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_download_a_ticket_attachment(): void
    {
        $this->seed(RolesSeeder::class);
        Storage::fake('local');

        $staff = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $staff->currentTeam;
        setPermissionsTeamId($team->id);
        $staff->assignRole('manager');
        $this->actingAs($staff);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        Storage::disk('local')->put('ticket-attachments/report.pdf', 'binary');
        $ticket = Ticket::factory()->create([
            'team_id' => $team->id,
            'attachment' => 'ticket-attachments/report.pdf',
        ]);

        Livewire::test(ListTickets::class)
            ->callTableAction('downloadAttachment', $ticket)
            ->assertFileDownloaded('report.pdf');
    }
}
