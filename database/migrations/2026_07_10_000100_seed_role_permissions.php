<?php

declare(strict_types=1);

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * No-lockout seed for F4 role enforcement: mints the permission catalog and
 * assigns the system-role matrix on existing deploys, so every system role
 * holds its permissions BEFORE any enforcement (slices 3b/3c) checks can().
 * Idempotent; safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::sync();
    }

    public function down(): void
    {
        // Intentionally irreversible: dropping the catalog / role grants would
        // strip access from live roles. Leave the seeded permissions in place.
    }
};
