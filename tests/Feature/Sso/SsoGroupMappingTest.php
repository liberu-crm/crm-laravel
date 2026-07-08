<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Enums\Role;
use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoGroupMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function fakeIdp(array $groups): void
    {
        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://idp.example.com/authorize',
                'token_endpoint' => 'https://idp.example.com/token',
                'userinfo_endpoint' => 'https://idp.example.com/userinfo',
            ]),
            'https://idp.example.com/token' => Http::response(['access_token' => 'access-123']),
            'https://idp.example.com/userinfo' => Http::response([
                'email' => 'newhire@acme.com',
                'groups' => $groups,
            ]),
        ]);
    }

    private function connection(Team $team, ?array $mappings): SsoConnection
    {
        return SsoConnection::factory()->create([
            'team_id' => $team->id,
            'issuer_url' => 'https://idp.example.com',
            'client_id' => 'client-abc',
            'client_secret' => 'shhh',
            'enabled' => true,
            'allow_jit' => true,
            'allowed_domain' => 'acme.com',
            'role_mappings' => $mappings,
        ]);
    }

    private function hitCallback(Team $team): void
    {
        $this->withSession(['sso_state' => 'st', 'sso_nonce' => 'n', 'sso_verifier' => 'v', 'sso_team' => $team->id])
            ->get(route('sso.callback', $team).'?code=auth-code&state=st');
    }

    public function test_role_for_groups_resolves_mapped_team_role(): void
    {
        $team = Team::factory()->create();
        $conn = $this->connection($team, ['idp-managers' => 'manager', 'idp-admins' => 'super_admin']);

        $this->assertSame(Role::Manager, $conn->roleForGroups(['idp-managers']));
        $this->assertNull($conn->roleForGroups(['nope']));
        // A non-team role mapping is ignored.
        $this->assertNull($conn->roleForGroups(['idp-admins']));
    }

    public function test_jit_login_applies_the_mapped_role(): void
    {
        $team = Team::factory()->create();
        $this->connection($team, ['idp-managers' => 'manager']);
        $this->fakeIdp(['idp-managers']);

        $this->hitCallback($team);

        $user = User::where('email', 'newhire@acme.com')->first();
        $this->assertNotNull($user);
        setPermissionsTeamId($team->id);
        $this->assertTrue($user->fresh()->hasRole(Role::Manager->value));
    }

    public function test_no_matching_group_keeps_the_default_role(): void
    {
        $team = Team::factory()->create();
        $this->connection($team, ['idp-managers' => 'manager']);
        $this->fakeIdp(['some-other-group']);

        $this->hitCallback($team);

        $user = User::where('email', 'newhire@acme.com')->first();
        setPermissionsTeamId($team->id);
        $this->assertTrue($user->fresh()->hasRole(Role::Free->value));
        $this->assertFalse($user->fresh()->hasRole(Role::Manager->value));
    }
}
