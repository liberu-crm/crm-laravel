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
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SsoJitProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function connection(Team $team, array $overrides = []): SsoConnection
    {
        return SsoConnection::factory()->create(array_merge([
            'team_id' => $team->id,
            'issuer_url' => 'https://idp.example.com',
            'client_id' => 'client-abc',
            'client_secret' => 'shhh',
            'enabled' => true,
        ], $overrides));
    }

    private function fakeIdp(string $email): void
    {
        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://idp.example.com/authorize',
                'token_endpoint' => 'https://idp.example.com/token',
                'userinfo_endpoint' => 'https://idp.example.com/userinfo',
            ]),
            'https://idp.example.com/token' => Http::response(['access_token' => 'access-123']),
            'https://idp.example.com/userinfo' => Http::response(['email' => $email, 'name' => 'New Hire']),
        ]);
    }

    private function hitCallback(Team $team): TestResponse
    {
        return $this->withSession(['sso_state' => 'st-1', 'sso_team' => $team->id])
            ->get(route('sso.callback', $team).'?code=auth-code&state=st-1');
    }

    public function test_non_member_denied_when_jit_off(): void
    {
        $this->fakeIdp('newhire@acme.com');
        $team = Team::factory()->create();
        $this->connection($team, ['allow_jit' => false]);

        $this->hitCallback($team)->assertForbidden();
        $this->assertGuest();
    }

    public function test_jit_provisions_and_logs_in_new_user_matching_domain(): void
    {
        $this->fakeIdp('newhire@acme.com');
        $team = Team::factory()->create();
        $this->connection($team, ['allow_jit' => true, 'allowed_domain' => 'acme.com']);

        $this->hitCallback($team)->assertRedirect();

        $user = User::where('email', 'newhire@acme.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->belongsToTeam($team));
        $this->assertAuthenticatedAs($user);
        setPermissionsTeamId($team->id);
        $this->assertTrue($user->fresh()->hasRole(Role::Free->value));
    }

    public function test_jit_rejects_email_outside_allowed_domain(): void
    {
        $this->fakeIdp('outsider@evil.com');
        $team = Team::factory()->create();
        $this->connection($team, ['allow_jit' => true, 'allowed_domain' => 'acme.com']);

        $this->hitCallback($team)->assertForbidden();
        $this->assertGuest();
        $this->assertNull(User::where('email', 'outsider@evil.com')->first());
    }

    public function test_jit_allows_any_domain_when_unset(): void
    {
        $this->fakeIdp('anyone@whatever.com');
        $team = Team::factory()->create();
        $this->connection($team, ['allow_jit' => true, 'allowed_domain' => null]);

        $this->hitCallback($team)->assertRedirect();
        $this->assertNotNull(User::where('email', 'anyone@whatever.com')->first());
    }

    public function test_jit_is_idempotent_for_existing_non_member_user(): void
    {
        $this->fakeIdp('existing@acme.com');
        $team = Team::factory()->create();
        $this->connection($team, ['allow_jit' => true, 'allowed_domain' => 'acme.com']);
        User::factory()->create(['email' => 'existing@acme.com']);

        $this->hitCallback($team)->assertRedirect();

        $this->assertSame(1, User::where('email', 'existing@acme.com')->count());
        $user = User::where('email', 'existing@acme.com')->first();
        $this->assertTrue($user->belongsToTeam($team));
        $this->assertAuthenticatedAs($user);
    }
}
