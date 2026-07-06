<?php

namespace App\Jobs\Middleware;

use App\Support\TenantContext;

/**
 * Restores the dispatching team's tenant context around a job's execution so
 * IsTenantModel-scoped queries filter correctly inside the worker, then clears
 * it in a finally block so a reused worker process can't leak the team into
 * the next job.
 */
class RestoreTenantContext
{
    public function handle(object $job, callable $next): mixed
    {
        TenantContext::set($job->tenantId ?? null);

        try {
            return $next($job);
        } finally {
            TenantContext::clear();
        }
    }
}
