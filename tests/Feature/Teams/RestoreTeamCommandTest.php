<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Jobs\RestoreTeamBackup;
use App\Models\Team;
use App\Models\TeamBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RestoreTeamCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_queues_a_restore(): void
    {
        Bus::fake();
        $team = Team::factory()->create();
        $backup = TeamBackup::factory()->create(['team_id' => $team->id, 'status' => 'completed', 'path' => 'backups/x.zip']);

        $this->artisan('team:restore', ['backup' => $backup->id])->assertSuccessful();

        Bus::assertDispatched(RestoreTeamBackup::class);
    }

    public function test_command_fails_for_unknown_backup(): void
    {
        Bus::fake();

        $this->artisan('team:restore', ['backup' => 999999])->assertFailed();

        Bus::assertNotDispatched(RestoreTeamBackup::class);
    }
}
