<?php

declare(strict_types=1);

namespace Tests\Feature\Saml;

use App\Models\SamlConnection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SamlSloTest extends TestCase
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
            'idp_slo_url' => 'https://idp.example.com/slo',
            'idp_x509_cert' => $this->idpCertPem,
            'enabled' => true,
        ], $overrides));

        return $team;
    }

    public function test_sp_metadata_advertises_the_single_logout_service(): void
    {
        $team = $this->connectedTeam();

        $this->get('/saml/'.$team->id.'/metadata')
            ->assertOk()
            ->assertSee('SingleLogoutService', false)
            ->assertSee('/saml/'.$team->id.'/sls', false);
    }

    public function test_saml_session_logout_redirects_to_the_idp_slo_with_a_logout_request(): void
    {
        $team = $this->connectedTeam();
        $user = User::factory()->create();
        $team->users()->attach($user);
        $this->actingAs($user);
        session([
            'sso_authenticated' => true,
            'sso_saml_nameid' => 'alice@corp.example',
            'sso_saml_session_index' => '_sess123',
            'sso_team' => $team->id,
        ]);

        $response = $this->post(route('filament.app.auth.logout'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://idp.example.com/slo', $location);
        $this->assertStringContainsString('SAMLRequest=', $location);
        $this->assertGuest();
    }

    public function test_saml_session_without_slo_url_logs_out_locally(): void
    {
        $team = $this->connectedTeam(['idp_slo_url' => null]);
        $user = User::factory()->create();
        $team->users()->attach($user);
        $this->actingAs($user);
        session([
            'sso_authenticated' => true,
            'sso_saml_nameid' => 'alice@corp.example',
            'sso_team' => $team->id,
        ]);

        $this->post(route('filament.app.auth.logout'))->assertRedirect('/login');
    }

    public function test_sls_processes_a_logout_response_and_returns_to_login(): void
    {
        $team = $this->connectedTeam();
        $sls = url('/saml/'.$team->id.'/sls');
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $xml = '<samlp:LogoutResponse xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_r'.bin2hex(random_bytes(4)).'" Version="2.0" IssueInstant="'.$now.'" Destination="'.$sls.'">'
            .'<saml:Issuer>https://idp.example.com/entity</saml:Issuer>'
            .'<samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status>'
            .'</samlp:LogoutResponse>';
        // HTTP-Redirect binding: deflate + base64.
        $encoded = base64_encode((string) gzdeflate($xml));

        $this->get('/saml/'.$team->id.'/sls?SAMLResponse='.urlencode($encoded))
            ->assertRedirect('/login');
    }

    public function test_acs_login_stores_the_name_id_and_session_index(): void
    {
        $team = $this->connectedTeam();
        $member = User::factory()->create(['email' => 'alice@corp.example']);
        $team->users()->attach($member);
        $rid = '_req'.bin2hex(random_bytes(8));
        $response = $this->buildSignedResponse($team, 'alice@corp.example', $rid);

        $this->withSession(['saml_request_id' => $rid])
            ->call('POST', '/saml/'.$team->id.'/acs', ['SAMLResponse' => $response])
            ->assertRedirect('/app');

        $this->assertSame('alice@corp.example', session('sso_saml_nameid'));
        $this->assertSame($team->id, session('sso_team'));
    }
}
