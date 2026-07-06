<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\DealResource;
use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DealResourceTest extends TestCase
{
    use RefreshDatabase;

    private function actingManagerWithTeam(): Team
    {
        Role::findOrCreate('manager', 'web');
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->ownedTeams->first();
        $user->current_team_id = $team->id;
        $user->save();
        $user->assignRole('manager');
        $this->actingAs($user);

        return $team;
    }

    public function test_index_page_mounts(): void
    {
        $team = $this->actingManagerWithTeam();
        $this->get('/app/'.$team->id.'/'.DealResource::getSlug())
            ->assertStatus(200);
    }

    public function test_create_page_mounts(): void
    {
        $team = $this->actingManagerWithTeam();
        $this->get('/app/'.$team->id.'/'.DealResource::getSlug().'/create')
            ->assertStatus(200);
    }

    public function test_edit_page_mounts(): void
    {
        $team = $this->actingManagerWithTeam();
        $deal = Deal::factory()->create(['team_id' => $team->id]);

        $this->get('/app/'.$team->id.'/'.DealResource::getSlug().'/'.$deal->id.'/edit')
            ->assertStatus(200);
    }
}
