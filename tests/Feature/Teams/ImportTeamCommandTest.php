<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTeamCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_imports_a_zip_into_a_new_team(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $source = Team::factory()->create();
        Contact::factory()->create(['team_id' => $source->id]);

        $result = (new TeamBackupService)->backup($source, 'local');
        Contact::withoutGlobalScope('tenant')->where('team_id', $source->id)->delete();
        $path = Storage::disk('local')->path($result['path']);

        $this->artisan('team:import', ['path' => $path, '--name' => 'Imported', '--owner' => $owner->id])
            ->assertSuccessful();

        $new = Team::where('name', 'Imported')->first();
        $this->assertNotNull($new);
        $this->assertSame($owner->id, $new->user_id);
        $this->assertSame(1, Contact::withoutGlobalScope('tenant')->where('team_id', $new->id)->count());
    }

    public function test_command_fails_for_a_missing_file(): void
    {
        $this->artisan('team:import', ['path' => '/no/such/file.zip'])->assertFailed();
    }
}
