<?php

namespace App\Support;

use Filament\Facades\Filament;
use Throwable;

/**
 * Resolves the "current team" that tenant-scoped models filter by.
 *
 * Resolution order:
 *  1. An explicit runtime override (set()/clear()) — used by jobs, console,
 *     tests, and any context outside a Filament panel request.
 *  2. The Filament panel tenant (app panel is team-scoped; admin panel has
 *     none, so it resolves to null and queries stay un-scoped).
 *
 * Null result = no scoping (admin/global/console). This is what keeps the
 * global scope inert until a tenant context genuinely exists.
 */
class TenantContext
{
    protected static ?int $teamId = null;

    public static function set(?int $teamId): void
    {
        static::$teamId = $teamId;
    }

    public static function clear(): void
    {
        static::$teamId = null;
    }

    public static function currentId(): ?int
    {
        if (static::$teamId !== null) {
            return static::$teamId;
        }

        // Filament::getTenant() throws when there is no current panel
        // (console, queue, plain HTTP) — treat that as "un-scoped".
        try {
            $tenant = Filament::getTenant();
        } catch (Throwable) {
            return null;
        }

        return $tenant ? (int) $tenant->getKey() : null;
    }
}
