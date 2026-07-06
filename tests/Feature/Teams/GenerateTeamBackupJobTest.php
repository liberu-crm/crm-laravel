<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Jobs\GenerateTeamBackup;
use App\Models\Contact;
use App\Models\Team;
use App\Models\TeamBackup;
use App\Services\TeamBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class GenerateTeamBackupJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_completes_and_records_path_and_size(): void
    {
        Storage::fake('local');
        $team = Team::factory()->create();
        Contact::factory()->create(['team_id' => $team->id]);
        $backup = TeamBackup::factory()->create(['team_id' => $team->id, 'disk' => 'local']);

        (new GenerateTeamBackup($backup->id))->handle(app(TeamBackupService::class));

        $backup->refresh();
        $this->assertSame('completed', $backup->status);
        $this->assertNotNull($backup->path);
        $this->assertGreaterThan(0, $backup->size_bytes);
        Storage::disk('local')->assertExists($backup->path);
    }

    public function test_job_records_failure(): void
    {
        $team = Team::factory()->create();
        $backup = TeamBackup::factory()->create(['team_id' => $team->id, 'disk' => 'local']);

        $service = new class extends TeamBackupService
        {
            public function backup(Team $team, ?string $disk = null): array
            {
                throw new RuntimeException('boom');
            }
        };

        try {
            (new GenerateTeamBackup($backup->id))->handle($service);
            $this->fail('expected the job to rethrow');
        } catch (Throwable $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $backup->refresh();
        $this->assertSame('failed', $backup->status);
        $this->assertStringContainsString('boom', (string) $backup->error);
    }
}
