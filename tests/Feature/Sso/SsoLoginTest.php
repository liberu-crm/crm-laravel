<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoLoginTest extends TestCase
{
    use RefreshDatabase;

    private function connection(Team $team, bool $enabled = true): SsoConnection
    {
        return SsoConnection::factory()->create([
            'team_id' => $team->id,
            'issuer_url' => 'https://idp.example.com',
            'client_id' => 'client-abc',
            'client_secret' => 'shhh',
            'enabled' => $enabled,
        ]);
    }

    private function fakeIdp(string $email = 'member@example.com'): void
    {
        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://idp.example.com/authorize',
                'token_endpoint' => 'https://idp.example.com/token',
                'userinfo_endpoint' => 'https://idp.example.com/userinfo',
            ]),
            'https://idp.example.com/token' => Http::response(['access_token' => 'access-123']),
            'https://idp.example.com/userinfo' => Http::response(['email' => $email]),
        ]);
    }

    public function test_redirect_sends_the_user_to_the_idp_with_state(): void
    {
        $this->fakeIdp();
        $team = Team::factory()->create();
        $this->connection($team);

        $response = $this->get(route('sso.redirect', $team));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://idp.example.com/authorize', $location);
        $this->assertStringContainsString('client_id=client-abc', $location);
        $this->assertStringContainsString('state=', $location);
        $response->assertSessionHas('sso_state');
    }

    public function test_callback_logs_in_a_matching_team_member(): void
    {
        $this->fakeIdp('member@example.com');
        $team = Team::factory()->create();
        $this->connection($team);
        $member = User::factory()->create(['email' => 'member@example.com', 'email_verified_at' => now()]);
        $team->users()->attach($member);

        $response = $this->withSession(['sso_state' => 'st-1', 'sso_team' => $team->id])
            ->get(route('sso.callback', $team).'?code=auth-code&state=st-1');

        $response->assertRedirect();
        $this->assertAuthenticatedAs($member);
        $this->assertSame($team->id, $member->fresh()->current_team_id);
    }

    public function test_callback_rejects_a_mismatched_state(): void
    {
        $this->fakeIdp();
        $team = Team::factory()->create();
        $this->connection($team);

        $this->withSession(['sso_state' => 'expected', 'sso_team' => $team->id])
            ->get(route('sso.callback', $team).'?code=auth-code&state=forged')
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_callback_rejects_a_non_member_email(): void
    {
        $this->fakeIdp('stranger@example.com');
        $team = Team::factory()->create();
        $this->connection($team);
        User::factory()->create(['email' => 'stranger@example.com']); // exists but not in team

        $this->withSession(['sso_state' => 'st-1', 'sso_team' => $team->id])
            ->get(route('sso.callback', $team).'?code=auth-code&state=st-1')
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_redirect_404s_for_a_disabled_connection(): void
    {
        $team = Team::factory()->create();
        $this->connection($team, enabled: false);

        $this->get(route('sso.redirect', $team))->assertNotFound();
    }
}
