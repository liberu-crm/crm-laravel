<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SamlConnection;
use App\Models\Team;
use App\Services\Sso\SamlSettings;
use Illuminate\Http\RedirectResponse;
use OneLogin\Saml2\Auth as SamlAuth;

/**
 * SP-initiated SAML login for a team's members (G2 SAML slice 1: AuthnRequest +
 * redirect). The ACS that consumes the IdP's signed response is a later slice.
 */
class SamlLoginController extends Controller
{
    public function redirect(Team $team): RedirectResponse
    {
        $connection = $this->enabledConnection($team);

        $auth = new SamlAuth(SamlSettings::for($team, $connection));

        // stay=true returns the IdP redirect URL instead of exiting, so we can
        // stash the request id first (bound to InResponseTo at the ACS).
        $url = $auth->login(returnTo: null, parameters: [], forceAuthn: false, isPassive: false, stay: true);

        session([
            'saml_request_id' => $auth->getLastRequestID(),
            'saml_team' => $team->getKey(),
        ]);

        return redirect()->away($url);
    }

    private function enabledConnection(Team $team): SamlConnection
    {
        // Login is pre-auth (no tenant context), so read the connection unscoped.
        $connection = SamlConnection::withoutGlobalScope('tenant')
            ->where('team_id', $team->getKey())
            ->where('enabled', true)
            ->first();

        abort_unless($connection instanceof SamlConnection, 404);

        return $connection;
    }
}
