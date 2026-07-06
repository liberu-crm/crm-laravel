<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\TeamBackupResource\Pages\ListTeamBackups;
use App\Jobs\RestoreTeamBackup;
use App\Models\Contact;
use App\Models\Team;
use App\Models\TeamBackup;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class TeamBackupRestoreActionTest extends TestCase
{
    use RefreshDatabase;

    private function actingSuperAdmin(): void
    {
        $this->seed(RolesSeeder::class);
        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_restore_action_queues_for_an_empty_team(): void
    {
        Bus::fake();
        $this->actingSuperAdmin();
        $team = Team::factory()->create();
        $backup = TeamBackup::factory()->create([
            'team_id' => $team->id, 'status' => 'completed', 'path' => 'backups/x.zip',
        ]);

        Livewire::test(ListTeamBackups::class)
            ->callTableAction('restore', $backup);

        Bus::assertDispatched(RestoreTeamBackup::class);
    }

    public function test_restore_action_refuses_a_non_empty_team(): void
    {
        Bus::fake();
        $this->actingSuperAdmin();
        $team = Team::factory()->create();
        Contact::factory()->create(['team_id' => $team->id]);
        $backup = TeamBackup::factory()->create([
            'team_id' => $team->id, 'status' => 'completed', 'path' => 'backups/x.zip',
        ]);

        Livewire::test(ListTeamBackups::class)
            ->callTableAction('restore', $backup);

        Bus::assertNotDispatched(RestoreTeamBackup::class);
    }
}
