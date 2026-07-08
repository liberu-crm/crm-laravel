<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Sso\ProvisionSsoUser;
use App\Exceptions\SsoException;
use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\Sso\OidcClient;
use App\Services\TeamManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * OIDC login for a team's members (G2 slice 2). No just-in-time provisioning
 * here — an email with no matching team member is denied (that is slice 3).
 */
class SsoLoginController extends Controller
{
    public function redirect(Team $team, OidcClient $oidc): RedirectResponse
    {
        $connection = $this->enabledConnection($team);

        $state = Str::random(40);
        $nonce = Str::random(40);
        // PKCE (RFC 7636): bind the auth code to this session's secret verifier.
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        session(['sso_state' => $state, 'sso_nonce' => $nonce, 'sso_verifier' => $verifier, 'sso_team' => $team->getKey()]);

        return redirect()->away(
            $oidc->authorizeUrl($connection, route('sso.callback', $team), $state, $nonce, $challenge)
        );
    }

    public function callback(Team $team, Request $request, OidcClient $oidc): RedirectResponse
    {
        // CSRF: the state we minted on redirect must come back unchanged.
        $state = $request->query('state');
        abort_unless(is_string($state) && $state === session('sso_state'), 403, 'Invalid SSO state.');
        $nonce = (string) session('sso_nonce');
        $verifier = (string) session('sso_verifier');
        $request->session()->forget(['sso_state', 'sso_nonce', 'sso_verifier', 'sso_team']);

        $connection = $this->enabledConnection($team);

        try {
            $tokens = $oidc->exchangeCode($connection, (string) $request->query('code'), route('sso.callback', $team), $verifier);

            // Prefer the cryptographically verified id_token; fall back to userinfo
            // for providers/flows that don't return one (keeps older configs working).
            $idToken = $tokens['id_token'] ?? null;
            if (is_string($idToken) && $idToken !== '') {
                $claims = $oidc->validateIdToken($connection, $idToken, $nonce);
            } else {
                $accessToken = $tokens['access_token'] ?? null;
                $claims = is_string($accessToken) ? $oidc->userinfo($connection, $accessToken) : [];
            }
        } catch (SsoException) {
            abort(403, 'SSO verification failed.');
        }

        $email = $claims['email'] ?? null;
        $name = $claims['name'] ?? null;

        $user = is_string($email) ? User::where('email', $email)->first() : null;

        if (! ($user instanceof User && $user->belongsToTeam($team))) {
            // Not already a member — provision just-in-time if the connection
            // allows it and the email is in the allowed domain, else deny.
            abort_unless(is_string($email) && $this->jitAllowed($connection, $email), 403, 'No access for this account.');
            $user = app(ProvisionSsoUser::class)($team, $email, is_string($name) ? $name : null);
        }

        // Sync the team role from the IdP's groups claim, if the connection maps
        // one. Only when it differs (avoids re-roling + auditing on every login);
        // the owner is left as-is (changeTeamRole throws, caught).
        $groups = $claims['groups'] ?? [];
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
        // Marks this session as SSO-established so enforcement doesn't bounce it.
        $request->session()->put('sso_authenticated', true);

        return redirect()->intended('/app');
    }

    private function jitAllowed(SsoConnection $connection, string $email): bool
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

    private function enabledConnection(Team $team): SsoConnection
    {
        // Login is pre-auth (no tenant context), so read the connection unscoped.
        $connection = SsoConnection::withoutGlobalScope('tenant')
            ->where('team_id', $team->getKey())
            ->where('enabled', true)
            ->first();

        abort_unless($connection instanceof SsoConnection, 404);

        return $connection;
    }
}
