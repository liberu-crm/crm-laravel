<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SsoIdTokenTest extends TestCase
{
    use RefreshDatabase;

    private string $privatePem;

    /** @var array<string, mixed> */
    private array $jwks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        $this->privatePem = (string) $pem;
        $details = openssl_pkey_get_details($key);

        $this->jwks = ['keys' => [[
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => 'test-key',
            'n' => $this->b64($details['rsa']['n']),
            'e' => $this->b64($details['rsa']['e']),
        ]]];
    }

    private function b64(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function idToken(array $overrides = [], ?string $signingKey = null): string
    {
        $claims = array_merge([
            'iss' => 'https://idp.example.com',
            'aud' => 'client-abc',
            'exp' => time() + 3600,
            'iat' => time(),
            'nonce' => 'nonce-1',
            'sub' => 'idp-sub-123',
            'email' => 'member@example.com',
            'name' => 'SSO Member',
        ], $overrides);

        return JWT::encode($claims, $signingKey ?? $this->privatePem, 'RS256', 'test-key');
    }

    private function fakeIdp(string $idToken, ?array $jwks = null): void
    {
        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://idp.example.com/authorize',
                'token_endpoint' => 'https://idp.example.com/token',
                'userinfo_endpoint' => 'https://idp.example.com/userinfo',
                'jwks_uri' => 'https://idp.example.com/jwks',
            ]),
            'https://idp.example.com/token' => Http::response(['access_token' => 'access-123', 'id_token' => $idToken]),
            'https://idp.example.com/jwks' => Http::response($jwks ?? $this->jwks),
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

    private function hitCallback(Team $team): TestResponse
    {
        return $this->withSession(['sso_state' => 'st-1', 'sso_nonce' => 'nonce-1', 'sso_team' => $team->id])
            ->get(route('sso.callback', $team).'?code=auth-code&state=st-1');
    }

    public function test_valid_signed_id_token_logs_in_member(): void
    {
        $this->fakeIdp($this->idToken());
        $team = Team::factory()->create();
        $this->connection($team);
        $member = User::factory()->create(['email' => 'member@example.com', 'email_verified_at' => now()]);
        $team->users()->attach($member);

        $this->hitCallback($team)->assertRedirect();
        $this->assertAuthenticatedAs($member);
    }

    public function test_id_token_signed_by_a_different_key_is_rejected(): void
    {
        $foreign = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($foreign, $foreignPem);
        $this->fakeIdp($this->idToken([], $foreignPem)); // signed by a key not in the JWKS

        $team = Team::factory()->create();
        $this->connection($team);
        $member = User::factory()->create(['email' => 'member@example.com', 'email_verified_at' => now()]);
        $team->users()->attach($member);

        $this->hitCallback($team)->assertForbidden();
        $this->assertGuest();
    }

    public function test_id_token_with_wrong_audience_is_rejected(): void
    {
        $this->fakeIdp($this->idToken(['aud' => 'someone-else']));
        $team = Team::factory()->create();
        $this->connection($team);
        User::factory()->create(['email' => 'member@example.com', 'email_verified_at' => now()]);

        $this->hitCallback($team)->assertForbidden();
        $this->assertGuest();
    }

    public function test_id_token_with_wrong_nonce_is_rejected(): void
    {
        $this->fakeIdp($this->idToken(['nonce' => 'replayed']));
        $team = Team::factory()->create();
        $this->connection($team);
        User::factory()->create(['email' => 'member@example.com', 'email_verified_at' => now()]);

        $this->hitCallback($team)->assertForbidden();
        $this->assertGuest();
    }
}
