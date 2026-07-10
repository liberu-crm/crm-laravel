<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Mints the app-panel permission catalog and assigns the system-role
        // matrix. Idempotent — see App\Support\PermissionCatalog.
        PermissionCatalog::sync();
    }
}
