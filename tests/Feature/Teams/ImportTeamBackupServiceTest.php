<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Exceptions\TeamImportException;
use App\Models\Contact;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\ImportTeamBackupService;
use App\Services\TeamBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ImportTeamBackupServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Back up a team, then delete its rows to simulate a disjoint target env
     * (a real cross-env import lands in a DB that does not already hold this
     * team's data / colliding unique values). Returns the backup's local path.
     */
    private function backupThenClear(Team $source): string
    {
        $result = (new TeamBackupService)->backup($source, 'local');

        Task::withoutGlobalScope('tenant')->where('team_id', $source->id)->delete();
        Contact::withoutGlobalScope('tenant')->where('team_id', $source->id)->delete();

        return Storage::disk('local')->path($result['path']);
    }

    public function test_imports_a_backup_into_a_new_team_remapping_fks_and_users(): void
    {
        Storage::fake('local');
        $importer = User::factory()->create();
        $assignee = User::factory()->create();

        $source = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $source->id, 'name' => 'CrossEnv Co']);
        Task::factory()->create([
            'team_id' => $source->id, 'contact_id' => $contact->id, 'assigned_to' => $assignee->id,
        ]);

        $localZip = $this->backupThenClear($source);

        $new = (new ImportTeamBackupService)->import($localZip, 'Imported Team', $importer);

        $this->assertNotSame($source->id, $new->id);
        $this->assertSame($importer->id, $new->user_id);

        $newContact = DB::table('contacts')->where('team_id', $new->id)->first();
        $newTask = DB::table('tasks')->where('team_id', $new->id)->first();
        $this->assertNotNull($newContact);
        $this->assertNotNull($newTask);
        $this->assertSame('CrossEnv Co', $newContact->name);

        // New PK, cross-model FK rewired to the imported contact.
        $this->assertNotSame($contact->id, (int) $newContact->id);
        $this->assertSame((int) $newContact->id, (int) $newTask->contact_id);

        // User reference resolves to the importer, not the source-env assignee.
        $this->assertSame($importer->id, (int) $newTask->assigned_to);
    }

    public function test_import_is_atomic_and_leaves_no_orphan_team_on_failure(): void
    {
        Storage::fake('local');
        $importer = User::factory()->create();
        $source = Team::factory()->create();
        Contact::factory()->create(['team_id' => $source->id, 'email' => 'dupe@example.com']);

        // Do NOT clear the source: the imported contact's unique email collides,
        // so the insert fails mid-import.
        $result = (new TeamBackupService)->backup($source, 'local');
        $teamsBefore = DB::table('teams')->count();

        try {
            (new ImportTeamBackupService)->import(Storage::disk('local')->path($result['path']), 'X', $importer);
            $this->fail('expected a unique-collision failure');
        } catch (\Throwable) {
            // expected
        }

        // Team was created inside the transaction -> rolled back, no orphan.
        $this->assertSame($teamsBefore, DB::table('teams')->count());
    }

    public function test_rejects_a_bad_archive(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bad.zip', 'not a zip');
        $importer = User::factory()->create();

        $this->expectException(TeamImportException::class);
        (new ImportTeamBackupService)->import(Storage::disk('local')->path('bad.zip'), 'X', $importer);
    }

    public function test_rejects_wrong_format_version(): void
    {
        Storage::fake('local');
        $importer = User::factory()->create();
        $path = Storage::disk('local')->path('v2.zip');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('manifest.json', (string) json_encode(['format_version' => 99, 'team' => ['id' => 1, 'name' => 'x']]));
        $zip->close();

        $this->expectException(TeamImportException::class);
        (new ImportTeamBackupService)->import($path, 'X', $importer);
    }
}
