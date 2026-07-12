<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Sso\ProvisionSsoUser;
use App\Models\SamlConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\Sso\SamlSettings;
use App\Services\TeamManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OneLogin\Saml2\Auth as SamlAuth;

/**
 * SP-initiated SAML login for a team's members (G2 SAML). Slice 1: AuthnRequest
 * redirect. Slice 2: the ACS that validates the IdP's signed response and logs
 * an existing member in. JIT + group→role mapping are slice 3.
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

    public function acs(Team $team, Request $request): RedirectResponse
    {
        $connection = $this->enabledConnection($team);

        // OneLogin reads the SAMLResponse from the $_POST superglobal; bridge the
        // Laravel request into it so this works under the test client too.
        $_POST['SAMLResponse'] = $request->input('SAMLResponse');
        if ($request->filled('RelayState')) {
            $_POST['RelayState'] = $request->input('RelayState');
        }

        // Bind the response to the AuthnRequest we minted (InResponseTo), then
        // clear it so a captured response can't be replayed against the session.
        $requestId = $request->session()->pull('saml_request_id');
        $request->session()->forget('saml_team');

        $auth = new SamlAuth(SamlSettings::for($team, $connection));
        $auth->processResponse($requestId);

        // strict=true + wantAssertionsSigned: any signature/condition/audience/
        // InResponseTo/replay failure lands in getErrors().
        abort_if($auth->getErrors() !== [] || ! $auth->isAuthenticated(), 403, 'SAML verification failed.');

        $email = $auth->getNameId();
        $attributes = $auth->getAttributes();
        $user = is_string($email) ? User::where('email', $email)->first() : null;

        if (! ($user instanceof User && $user->belongsToTeam($team))) {
            // Not already a member — provision just-in-time if the connection
            // allows it and the email is in the allowed domain, else deny.
            abort_unless(is_string($email) && $this->jitAllowed($connection, $email), 403, 'No access for this account.');
            $name = $attributes['name'][0] ?? $attributes['displayName'][0] ?? null;
            $user = app(ProvisionSsoUser::class)($team, $email, is_string($name) ? $name : null);
        }

        // Map the IdP's groups attribute to a team role, if the connection maps
        // one and it differs (avoids re-roling + auditing every login; the owner
        // is left as-is — changeTeamRole throws, caught).
        $groups = $attributes['groups'] ?? [];
        $mappedRole = is_array($groups) ? $connection->roleForGroups($groups) : null;
        if ($mappedRole !== null) {
            setPermissionsTeamId($team->getKey());
            if (! $user->hasRole($mappedRole->value)) {
                try {
                    app(TeamManagementService::class)->changeTeamRole($user, $team, $mappedRole);
                } catch (InvalidArgumentException) {
                    // Owner or otherwise unassignable — keep the existing role.
                }
            }
        }

        $user->forceFill(['current_team_id' => $team->getKey()])->save();
        Auth::login($user);
        $request->session()->regenerate();
        // Marks the session SSO-established so enforcement doesn't bounce it.
        $request->session()->put('sso_authenticated', true);

        return redirect()->intended('/app');
    }

    private function jitAllowed(SamlConnection $connection, string $email): bool
    {
        if (! $connection->getAttribute('allow_jit')) {
            return false;
        }

        $domain = $connection->getAttribute('allowed_domain');
        if (blank($domain)) {
            return true;
        }

        return Str::endsWith(Str::lower($email), '@'.Str::lower((string) $domain));
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
