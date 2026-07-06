<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establishes the tenant for API requests from the authenticated user's
 * current team, so the IsTenantModel global scope filters every query —
 * including route-model binding, which otherwise resolves any team's record
 * by id (cross-tenant IDOR).
 *
 * Resolves via the sanctum guard directly so it does not depend on middleware
 * ordering relative to auth:sanctum. Must run before SubstituteBindings.
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();
        TenantContext::set($user instanceof User ? $user->currentTeam?->getKey() : null);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Octane/RoadRunner reuses workers across requests — never let a
        // team leak into the next request.
        TenantContext::clear();
    }
}
