<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Creates the six global system roles (team_id = null), mints the
        // app-panel permission catalog, and assigns the system-role matrix.
        // Idempotent — see App\Support\PermissionCatalog.
        PermissionCatalog::sync();
    }
}
