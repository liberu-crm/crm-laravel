<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;
use App\Support\SsoEnforcement;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(Team $team): User
    {
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team->users()->attach($user);
        $user->forceFill(['current_team_id' => $team->id])->save();
        setPermissionsTeamId($team->id);
        $user->assignRole('manager');

        return $user;
    }

    private function connection(Team $team, array $overrides = []): SsoConnection
    {
        return SsoConnection::factory()->create(array_merge([
            'team_id' => $team->id,
            'enabled' => true,
            'require_sso' => true,
        ], $overrides));
    }

    public function test_enforced_user_without_sso_session_is_bounced_to_sso(): void
    {
        $team = Team::factory()->create();
        $this->connection($team);
        $user = $this->member($team);

        $this->actingAs($user)
            ->get('/app/'.$team->id)
            ->assertRedirect(route('sso.redirect', $team));

        $this->assertGuest();
    }

    public function test_enforced_user_with_sso_session_is_allowed(): void
    {
        $team = Team::factory()->create();
        $this->connection($team);
        $user = $this->member($team);

        $response = $this->actingAs($user)
            ->withSession(['sso_authenticated' => true])
            ->get('/app/'.$team->id);

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_without_an_enforcing_team_proceeds(): void
    {
        $team = Team::factory()->create();
        $this->connection($team, ['require_sso' => false]);
        $user = $this->member($team);

        $this->actingAs($user)
            ->get('/app/'.$team->id)
            ->assertOk();
    }

    public function test_not_enforced_when_connection_disabled(): void
    {
        $team = Team::factory()->create();
        $this->connection($team, ['enabled' => false, 'require_sso' => true]);
        $user = $this->member($team);

        $this->assertNull(SsoEnforcement::enforcingTeamFor($user));

        $this->actingAs($user)
            ->get('/app/'.$team->id)
            ->assertOk();
    }

    public function test_enforcing_team_is_resolved(): void
    {
        $team = Team::factory()->create();
        $this->connection($team);
        $user = $this->member($team);

        $this->assertTrue(SsoEnforcement::enforcingTeamFor($user)?->is($team));
    }
}
