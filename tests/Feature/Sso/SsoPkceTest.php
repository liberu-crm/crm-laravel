<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoPkceTest extends TestCase
{
    use RefreshDatabase;

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

    private function connection(Team $team): SsoConnection
    {
        return SsoConnection::factory()->create([
            'team_id' => $team->id,
            'issuer_url' => 'https://idp.example.com',
            'client_id' => 'client-abc',
            'client_secret' => 'shhh',
            'enabled' => true,
        ]);
    }

    public function test_redirect_includes_a_pkce_challenge(): void
    {
        $this->fakeIdp();
        $team = Team::factory()->create();
        $this->connection($team);

        $response = $this->get(route('sso.redirect', $team));

        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('code_challenge=', $location);
        $this->assertStringContainsString('code_challenge_method=S256', $location);
        $response->assertSessionHas('sso_verifier');
    }

    public function test_callback_sends_the_code_verifier(): void
    {
        $this->fakeIdp('member@example.com');
        $team = Team::factory()->create();
        $this->connection($team);
        $member = User::factory()->create(['email' => 'member@example.com', 'email_verified_at' => now()]);
        $team->users()->attach($member);

        $this->withSession(['sso_state' => 'st-1', 'sso_nonce' => 'n-1', 'sso_verifier' => 'verifier-123', 'sso_team' => $team->id])
            ->get(route('sso.callback', $team).'?code=auth-code&state=st-1');

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/token')
            && ($request['code_verifier'] ?? null) === 'verifier-123');

        $this->assertAuthenticatedAs($member);
    }
}
