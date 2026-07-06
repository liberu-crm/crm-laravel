<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes Spatie permissions to the authenticated user's current team on every
 * request, BEFORE any role/gate/policy check, so hasRole()/can() resolve
 * per-team. Mirrors SetTenantContext's team resolution (sanctum first, then the
 * session guard) so it works on both API and web/panel requests.
 *
 * The app panel additionally re-scopes to the resolved Filament tenant via the
 * SwitchTeam listener (TenantSet event), which is the authoritative acting team
 * once the panel has resolved its tenant.
 */
class SetPermissionsTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user() ?? $request->user();

        setPermissionsTeamId($user instanceof User ? $user->currentTeam?->getKey() : null);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Octane/RoadRunner reuses workers across requests — never let a team
        // leak into the next request.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
