<?php

declare(strict_types=1);

namespace Tests\Feature\Saml;

use App\Models\SamlConnection;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SamlLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function team(bool $enabled): Team
    {
        $team = Team::factory()->create();
        SamlConnection::factory()->create([
            'team_id' => $team->id,
            'idp_sso_url' => 'https://idp.example.com/sso',
            'idp_entity_id' => 'https://idp.example.com/entity',
            'enabled' => $enabled,
        ]);

        return $team;
    }

    public function test_redirects_to_the_idp_with_an_authn_request(): void
    {
        $team = $this->team(enabled: true);

        $response = $this->get('/saml/'.$team->id.'/login');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://idp.example.com/sso', $location);
        $this->assertStringContainsString('SAMLRequest=', $location);
        // The request id is stashed for InResponseTo validation at the ACS.
        $this->assertNotEmpty(session('saml_request_id'));
    }

    public function test_404_when_no_enabled_connection(): void
    {
        $team = $this->team(enabled: false);

        $this->get('/saml/'.$team->id.'/login')->assertNotFound();
    }

    public function test_404_when_no_connection_at_all(): void
    {
        $team = Team::factory()->create();

        $this->get('/saml/'.$team->id.'/login')->assertNotFound();
    }
}
