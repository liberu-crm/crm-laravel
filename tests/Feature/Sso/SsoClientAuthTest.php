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

class SsoClientAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeIdp(): void
    {
        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://idp.example.com/authorize',
                'token_endpoint' => 'https://idp.example.com/token',
                'userinfo_endpoint' => 'https://idp.example.com/userinfo',
            ]),
            'https://idp.example.com/token' => Http::response(['access_token' => 'access-123']),
            'https://idp.example.com/userinfo' => Http::response(['email' => 'member@example.com']),
        ]);
    }

    private function login(Team $team): void
    {
        $member = User::factory()->create(['email' => 'member@example.com', 'email_verified_at' => now()]);
        $team->users()->attach($member);

        $this->withSession(['sso_state' => 'st', 'sso_nonce' => 'n', 'sso_verifier' => 'v', 'sso_team' => $team->id])
            ->get(route('sso.callback', $team).'?code=auth-code&state=st');
    }

    private function connection(Team $team, string $method): SsoConnection
    {
        return SsoConnection::factory()->create([
            'team_id' => $team->id,
            'issuer_url' => 'https://idp.example.com',
            'client_id' => 'client-abc',
            'client_secret' => 'shhh',
            'enabled' => true,
            'token_auth_method' => $method,
        ]);
    }

    public function test_basic_auth_sends_authorization_header_and_omits_body_secret(): void
    {
        $this->fakeIdp();
        $team = Team::factory()->create();
        $this->connection($team, 'client_secret_basic');

        $this->login($team);

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/token')) {
                return false;
            }

            return str_starts_with((string) $request->header('Authorization')[0], 'Basic ')
                && ! isset($request['client_secret']);
        });
        $this->assertAuthenticated();
    }

    public function test_post_auth_sends_secret_in_the_body(): void
    {
        $this->fakeIdp();
        $team = Team::factory()->create();
        $this->connection($team, 'client_secret_post');

        $this->login($team);

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/token')) {
                return false;
            }

            return ($request['client_secret'] ?? null) === 'shhh'
                && $request->header('Authorization') === [];
        });
    }
}
