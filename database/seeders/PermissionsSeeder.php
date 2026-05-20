<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Artisan::call('permissions:sync');

        // Define additional custom permissions
        $customPermissions = [
            'view_reports',
            'manage_users',
            'manage_roles',
            'manage_permissions',
        ];

        foreach ($customPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Ensure all Filament Shield resources have the necessary permissions
        $resources = config('filament-shield.entities.resources');
        $permissionPrefixes = config('filament-shield.permission_prefixes.resource');

        foreach ($resources as $resource) {
            foreach ($permissionPrefixes as $prefix) {
                Permission::firstOrCreate([
                    'name' => $prefix . '_' . strtolower($resource),
                    'guard_name' => 'web'
                ]);
            }
        }
    }
}
