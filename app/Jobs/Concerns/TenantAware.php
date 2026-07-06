<?php

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\RestoreTenantContext;
use App\Support\TenantContext;

/**
 * Makes a queued job remember the team it was dispatched for and restore it
 * inside the worker, so tenant-scoped (IsTenantModel) Eloquent queries stay
 * scoped instead of leaking across teams.
 *
 * A trait constructor cannot run automatically alongside the job's own
 * constructor, so opt in with ONE line in the job's constructor:
 *
 *   public function __construct(...) { ...; $this->captureTenant(); }
 *
 * captureTenant() snapshots the current tenant at dispatch time (serialized
 * with the job); the RestoreTenantContext middleware sets it back before
 * handle() runs and clears it afterwards.
 */
trait TenantAware
{
    public ?int $tenantId = null;

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RestoreTenantContext];
    }

    protected function captureTenant(): void
    {
        $this->tenantId = TenantContext::currentId();
    }
}
