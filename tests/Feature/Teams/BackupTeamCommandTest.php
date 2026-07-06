<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Jobs\GenerateTeamBackup;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class BackupTeamCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_queues_a_backup_for_a_valid_team(): void
    {
        Bus::fake();
        $team = Team::factory()->create();

        $this->artisan('team:backup', ['team' => $team->id])->assertSuccessful();

        $this->assertDatabaseHas('team_backups', [
            'team_id' => $team->id,
            'status' => 'pending',
        ]);
        Bus::assertDispatched(GenerateTeamBackup::class);
    }

    public function test_command_fails_for_unknown_team(): void
    {
        Bus::fake();

        $this->artisan('team:backup', ['team' => 999999])->assertFailed();

        Bus::assertNotDispatched(GenerateTeamBackup::class);
        $this->assertDatabaseCount('team_backups', 0);
    }
}
