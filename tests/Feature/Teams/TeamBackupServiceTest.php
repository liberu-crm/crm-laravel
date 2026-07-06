<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class TeamBackupServiceTest extends TestCase
{
    use RefreshDatabase;

    private function readEntry(string $path, string $entry): string
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($path)) === true);
        $contents = $zip->getFromName($entry);
        $zip->close();
        $this->assertNotFalse($contents, "zip entry `{$entry}` is missing");

        return $contents;
    }

    public function test_backup_produces_a_zip_with_model_json_and_manifest(): void
    {
        Storage::fake('local');
        $team = Team::factory()->create();
        Contact::factory()->create(['team_id' => $team->id, 'name' => 'Acme Buyer']);

        $result = (new TeamBackupService)->backup($team, 'local');

        Storage::disk('local')->assertExists($result['path']);
        $this->assertGreaterThan(0, $result['size']);

        $manifest = json_decode($this->readEntry($result['path'], 'manifest.json'), true);
        $this->assertSame($team->id, $manifest['team']['id']);
        $this->assertSame(1, $manifest['models']['Contact']);

        $this->assertStringContainsString('Acme Buyer', $this->readEntry($result['path'], 'models/Contact.json'));
    }

    public function test_backup_contains_only_the_target_teams_rows(): void
    {
        Storage::fake('local');
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        Contact::factory()->create(['team_id' => $teamA->id, 'name' => 'Mine Contact']);
        Contact::factory()->create(['team_id' => $teamB->id, 'name' => 'Other Contact']);
        Lead::factory()->create(['team_id' => $teamB->id]);

        $result = (new TeamBackupService)->backup($teamA, 'local');

        $contacts = $this->readEntry($result['path'], 'models/Contact.json');
        $this->assertStringContainsString('Mine Contact', $contacts);
        $this->assertStringNotContainsString('Other Contact', $contacts);

        $manifest = json_decode($this->readEntry($result['path'], 'manifest.json'), true);
        $this->assertSame(1, $manifest['models']['Contact']);
    }

    public function test_backup_includes_team_membership_extras(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'sales_rep']);

        $result = (new TeamBackupService)->backup($team, 'local');

        $manifest = json_decode($this->readEntry($result['path'], 'manifest.json'), true);
        $this->assertSame(1, $manifest['extras']['team_user']);
        $this->assertStringContainsString((string) $member->id, $this->readEntry($result['path'], 'extras/team_user.json'));
    }
}
