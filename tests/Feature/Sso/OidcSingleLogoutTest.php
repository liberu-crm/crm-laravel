<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OidcSingleLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_oidc_session_logout_redirects_to_the_idp_end_session_endpoint(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        session([
            'sso_authenticated' => true,
            'sso_id_token' => 'header.payload.sig',
            'sso_end_session_endpoint' => 'https://idp.example.com/logout',
        ]);

        $response = $this->post(route('filament.app.auth.logout'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://idp.example.com/logout?', $location);
        $this->assertStringContainsString('id_token_hint=header.payload.sig', $location);
        $this->assertStringContainsString('post_logout_redirect_uri=', $location);
        $this->assertGuest();
    }

    public function test_sso_session_without_end_session_endpoint_logs_out_locally(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        // SSO-established but the IdP advertised no end-session endpoint, so the
        // login stored neither id_token nor endpoint.
        session(['sso_authenticated' => true]);

        $this->post(route('filament.app.auth.logout'))->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_non_sso_logout_redirects_to_login(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->post(route('filament.app.auth.logout'))->assertRedirect('/login');
        $this->assertGuest();
    }
}
