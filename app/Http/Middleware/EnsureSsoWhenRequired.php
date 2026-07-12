<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SsoEnforcement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces SSO on the app panel: an authenticated user whose team mandates SSO
 * but whose session was not established via SSO is logged out and bounced to the
 * IdP. Runs on every /app request, so it can't be sidestepped by the login route
 * the user came through (Fortify /login, Filament admin login, ...).
 */
class EnsureSsoWhenRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $request->session()->get('sso_authenticated')) {
            $team = SsoEnforcement::enforcingTeamFor($user);

            if ($team !== null) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route(SsoEnforcement::loginRouteFor($team), $team);
            }
        }

        return $next($request);
    }
}
