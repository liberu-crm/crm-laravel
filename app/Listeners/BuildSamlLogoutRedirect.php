<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\SamlConnection;
use App\Models\Team;
use App\Services\Sso\SamlSettings;
use App\Support\SsoLogoutState;
use Illuminate\Auth\Events\Logout;
use OneLogin\Saml2\Auth as SamlAuth;

/**
 * On logout of a SAML SSO session, build the SP-initiated LogoutRequest URL
 * (OneLogin) targeting the IdP's Single Logout Service, and stash it on the
 * request-scoped SsoLogoutState. Runs during auth()->logout() (before Filament
 * invalidates the session), so the NameID / SessionIndex stored at ACS login are
 * still readable. Only fires for SAML sessions whose IdP advertises an SLO URL;
 * a team uses one protocol, so the OIDC listener no-ops for these.
 */
class BuildSamlLogoutRedirect
{
    public function __construct(private SsoLogoutState $state) {}

    public function handle(Logout $event): void
    {
        $nameId = session('sso_saml_nameid');
        $sessionIndex = session('sso_saml_session_index');
        $teamId = session('sso_team');

        if (! is_string($nameId) || $nameId === '' || blank($teamId)) {
            return;
        }

        $team = Team::find($teamId);
        if (! $team instanceof Team) {
            return;
        }

        $connection = SamlConnection::withoutGlobalScope('tenant')
            ->where('team_id', $teamId)
            ->where('enabled', true)
            ->first();

        if (! $connection instanceof SamlConnection || blank($connection->getAttribute('idp_slo_url'))) {
            return;
        }

        $auth = new SamlAuth(SamlSettings::for($team, $connection));
        $url = $auth->logout(
            returnTo: url('/login'),
            parameters: [],
            nameId: $nameId,
            sessionIndex: is_string($sessionIndex) && $sessionIndex !== '' ? $sessionIndex : null,
            stay: true,
        );

        $this->state->set($url);
    }
}
