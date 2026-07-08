<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Sso\ProvisionSsoUser;
use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\Sso\OidcClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
        session(['sso_state' => $state, 'sso_team' => $team->getKey()]);

        return redirect()->away(
            $oidc->authorizeUrl($connection, route('sso.callback', $team), $state)
        );
    }

    public function callback(Team $team, Request $request, OidcClient $oidc): RedirectResponse
    {
        // CSRF: the state we minted on redirect must come back unchanged.
        $state = $request->query('state');
        abort_unless(is_string($state) && $state === session('sso_state'), 403, 'Invalid SSO state.');
        $request->session()->forget(['sso_state', 'sso_team']);

        $connection = $this->enabledConnection($team);

        $accessToken = $oidc->exchangeCode($connection, (string) $request->query('code'), route('sso.callback', $team));
        $claims = $oidc->userinfo($connection, $accessToken);
        $email = $claims['email'] ?? null;
        $name = $claims['name'] ?? null;

        $user = is_string($email) ? User::where('email', $email)->first() : null;

        if (! ($user instanceof User && $user->belongsToTeam($team))) {
            // Not already a member — provision just-in-time if the connection
            // allows it and the email is in the allowed domain, else deny.
            abort_unless(is_string($email) && $this->jitAllowed($connection, $email), 403, 'No access for this account.');
            $user = app(ProvisionSsoUser::class)($team, $email, is_string($name) ? $name : null);
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
