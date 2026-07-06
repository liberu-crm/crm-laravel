<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Exceptions\TeamNotEmptyException;
use App\Exceptions\TeamRestoreException;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamBackup;
use App\Services\TeamBackupService;
use App\Services\TeamRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Throwable;
use ZipArchive;

class TeamRestoreServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Back up a team and return its completed TeamBackup row. */
    private function backupOf(Team $team): TeamBackup
    {
        $result = (new TeamBackupService)->backup($team, 'local');

        return TeamBackup::factory()->create([
            'team_id' => $team->id,
            'disk' => 'local',
            'path' => $result['path'],
            'size_bytes' => $result['size'],
            'status' => 'completed',
        ]);
    }

    public function test_round_trips_rows_with_original_ids_and_fks(): void
    {
        Storage::fake('local');
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'RoundTrip Co']);
        $task = Task::factory()->create(['team_id' => $team->id, 'contact_id' => $contact->id]);

        $backup = $this->backupOf($team);

        Task::withoutGlobalScope('tenant')->where('team_id', $team->id)->delete();
        Contact::withoutGlobalScope('tenant')->where('team_id', $team->id)->delete();
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);

        $restored = (new TeamRestoreService)->restore($backup);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'name' => 'RoundTrip Co', 'team_id' => $team->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'contact_id' => $contact->id]);
        $this->assertSame(1, $restored['Contact']);
    }

    public function test_refuses_to_restore_over_a_non_empty_team(): void
    {
        Storage::fake('local');
        $team = Team::factory()->create();
        Contact::factory()->create(['team_id' => $team->id]);
        $backup = $this->backupOf($team);

        // team still populated -> must refuse
        $this->expectException(TeamNotEmptyException::class);
        (new TeamRestoreService)->restore($backup);
    }

    public function test_rejects_a_non_completed_backup(): void
    {
        $team = Team::factory()->create();
        $backup = TeamBackup::factory()->create(['team_id' => $team->id, 'status' => 'pending']);

        $this->expectException(TeamRestoreException::class);
        (new TeamRestoreService)->restore($backup);
    }

    public function test_rejects_when_the_backup_file_is_missing(): void
    {
        Storage::fake('local');
        $team = Team::factory()->create();
        $backup = TeamBackup::factory()->create([
            'team_id' => $team->id,
            'disk' => 'local',
            'path' => 'backups/gone.zip',
            'status' => 'completed',
        ]);

        $this->expectException(TeamRestoreException::class);
        (new TeamRestoreService)->restore($backup);
    }

    public function test_restore_is_atomic_on_failure(): void
    {
        Storage::fake('local');
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);
        $backup = $this->backupOf($team);

        // Corrupt a later model's entry (Lead sorts after Contact) with an
        // unknown column so its insert throws mid-restore.
        $full = Storage::disk('local')->path((string) $backup->path);
        $zip = new ZipArchive;
        $zip->open($full);
        $zip->addFromString('models/Lead.json', (string) json_encode([['team_id' => $team->id, 'bogus_col' => 1]]));
        $zip->close();

        Contact::withoutGlobalScope('tenant')->where('team_id', $team->id)->delete();

        try {
            (new TeamRestoreService)->restore($backup);
            $this->fail('expected the restore to throw');
        } catch (Throwable) {
            // expected
        }

        // Contact inserted before Lead failed -> transaction rolled back -> gone.
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
        $this->assertSame(0, Lead::withoutGlobalScope('tenant')->where('team_id', $team->id)->count());
    }
}
