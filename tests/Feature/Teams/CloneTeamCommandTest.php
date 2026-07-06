<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Models\Pipeline;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloneTeamCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_clones_config_into_a_new_team(): void
    {
        $source = Team::factory()->create();
        Pipeline::factory()->create(['team_id' => $source->id]);
        $owner = User::factory()->create();

        $this->artisan('team:clone', [
            'source' => $source->id,
            '--name' => 'Fresh Team',
            '--owner' => $owner->id,
        ])->assertSuccessful();

        $new = Team::where('name', 'Fresh Team')->first();
        $this->assertNotNull($new);
        $this->assertSame($owner->id, $new->user_id);
        $this->assertSame(1, Pipeline::withoutGlobalScope('tenant')->where('team_id', $new->id)->count());
    }

    public function test_command_fails_for_unknown_source(): void
    {
        $this->artisan('team:clone', ['source' => 999999])->assertFailed();
    }
}
