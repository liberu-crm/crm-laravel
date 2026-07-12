<?php

declare(strict_types=1);

namespace Tests\Feature\Saml;

use App\Models\SamlConnection;
use App\Models\Team;
use App\Models\User;
use App\Support\SsoEnforcement;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SamlJitTest extends TestCase
{
    use RefreshDatabase;
    use SignsSamlResponses;

    /** @param array<string, mixed> $overrides */
    private function connectedTeam(array $overrides = []): Team
    {
        $this->idpKeypair();
        $team = Team::factory()->create();
        SamlConnection::factory()->create(array_merge([
            'team_id' => $team->id,
            'idp_entity_id' => 'https://idp.example.com/entity',
            'idp_sso_url' => 'https://idp.example.com/sso',
            'idp_x509_cert' => $this->idpCertPem,
            'enabled' => true,
        ], $overrides));

        return $team;
    }

    private function postAcs(Team $team, string $response, string $requestId): TestResponse
    {
        return $this->withSession(['saml_request_id' => $requestId])
            ->call('POST', '/saml/'.$team->id.'/acs', ['SAMLResponse' => $response]);
    }

    public function test_jit_disabled_denies_a_non_member(): void
    {
        $this->seed(RolesSeeder::class);
        $team = $this->connectedTeam(['allow_jit' => false]);
        $rid = '_req'.bin2hex(random_bytes(8));

        $this->postAcs($team, $this->buildSignedResponse($team, 'new@corp.example', $rid), $rid)
            ->assertForbidden();

        $this->assertNull(User::where('email', 'new@corp.example')->first());
        $this->assertGuest();
    }

    public function test_jit_provisions_an_allowed_domain_user_as_free(): void
    {
        $this->seed(RolesSeeder::class);
        $team = $this->connectedTeam(['allow_jit' => true, 'allowed_domain' => 'corp.example']);
        $rid = '_req'.bin2hex(random_bytes(8));

        $this->postAcs($team, $this->buildSignedResponse($team, 'new@corp.example', $rid), $rid)
            ->assertRedirect('/app');

        $user = User::where('email', 'new@corp.example')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->belongsToTeam($team));
        setPermissionsTeamId($team->id);
        $this->assertTrue($user->hasRole('free'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_jit_rejects_a_domain_mismatch(): void
    {
        $this->seed(RolesSeeder::class);
        $team = $this->connectedTeam(['allow_jit' => true, 'allowed_domain' => 'corp.example']);
        $rid = '_req'.bin2hex(random_bytes(8));

        $this->postAcs($team, $this->buildSignedResponse($team, 'outsider@other.example', $rid), $rid)
            ->assertForbidden();

        $this->assertNull(User::where('email', 'outsider@other.example')->first());
    }

    public function test_group_maps_to_a_team_role(): void
    {
        $this->seed(RolesSeeder::class);
        $team = $this->connectedTeam([
            'allow_jit' => true,
            'allowed_domain' => 'corp.example',
            'role_mappings' => ['engineering' => 'manager'],
        ]);
        $rid = '_req'.bin2hex(random_bytes(8));
        $response = $this->buildSignedResponse($team, 'lead@corp.example', $rid, ['groups' => ['engineering']]);

        $this->postAcs($team, $response, $rid)->assertRedirect('/app');

        $user = User::where('email', 'lead@corp.example')->first();
        setPermissionsTeamId($team->id);
        $this->assertTrue($user->hasRole('manager'));
        $this->assertFalse($user->hasRole('free'));
    }

    public function test_enforcement_bounces_a_saml_team_to_the_saml_login(): void
    {
        $this->seed(RolesSeeder::class);
        $team = $this->connectedTeam(['require_sso' => true]);
        $user = User::factory()->create();
        $team->users()->attach($user);
        $user->forceFill(['current_team_id' => $team->id])->save();

        // Enforcement resolves the SAML login route for this team.
        $this->assertSame($team->id, SsoEnforcement::enforcingTeamFor($user)?->id);
        $this->assertSame('saml.login', SsoEnforcement::loginRouteFor($team));

        // A non-SSO session hitting the app panel is bounced to the IdP.
        $this->actingAs($user)->get('/app')->assertRedirect('/saml/'.$team->id.'/login');
    }
}
