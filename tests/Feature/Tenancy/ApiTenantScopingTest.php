<?php

namespace Tests\Feature\Tenancy;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The REST API authenticates with Sanctum and has no team in the path, so a
 * request's tenant must come from the authenticated user's current team.
 * Without that, route-model binding (show/update/destroy) resolves any team's
 * record by id — a cross-tenant IDOR.
 */
class ApiTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    private function actingUserOnTeam(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->forceFill(['current_team_id' => $team->id])->save();
        Sanctum::actingAs($user);

        return [$user, $team];
    }

    public function test_index_only_returns_current_teams_contacts(): void
    {
        [, $teamA] = $this->actingUserOnTeam();
        $mine = Contact::factory()->create(['team_id' => $teamA->id]);
        $other = Contact::factory()->create(['team_id' => Team::factory()->create()->id]);

        $this->getJson('/api/v1/contacts')
            ->assertOk()
            ->assertJsonFragment(['id' => $mine->id])
            ->assertJsonMissing(['id' => $other->id]);
    }

    public function test_show_does_not_leak_another_teams_contact(): void
    {
        $this->actingUserOnTeam();
        $foreign = Contact::factory()->create(['team_id' => Team::factory()->create()->id]);

        $this->getJson("/api/v1/contacts/{$foreign->id}")->assertNotFound();
    }

    public function test_show_returns_own_teams_contact(): void
    {
        [, $teamA] = $this->actingUserOnTeam();
        $mine = Contact::factory()->create(['team_id' => $teamA->id]);

        $this->getJson("/api/v1/contacts/{$mine->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $mine->id]);
    }
}
