<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Support\SsoLogoutState;
use Illuminate\Auth\Events\Logout;

/**
 * On logout, if the session was established via OIDC SSO and the IdP advertises
 * an end-session endpoint, build the RP-initiated logout URL and stash it on the
 * request-scoped SsoLogoutState. Runs during auth()->logout() (before Filament
 * invalidates the session), so the id_token stored at login is still readable.
 * LogoutResponse reads the holder afterwards.
 */
class BuildSsoLogoutRedirect
{
    public function __construct(private SsoLogoutState $state) {}

    public function handle(Logout $event): void
    {
        $idToken = session('sso_id_token');
        $endSession = session('sso_end_session_endpoint');

        if (! is_string($idToken) || $idToken === '' || ! is_string($endSession) || $endSession === '') {
            return;
        }

        $this->state->set($endSession.'?'.http_build_query([
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => url('/login'),
        ]));
    }
}
