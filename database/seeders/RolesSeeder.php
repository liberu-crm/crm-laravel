<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminPermissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
        $adminRole->syncPermissions($adminPermissions);

        // Manager role
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerPermissions = Permission::whereIn('name', [
            'view_any_client', 'view_client', 'create_client', 'update_client',
            'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
            'view_any_opportunity', 'view_opportunity', 'create_opportunity', 'update_opportunity',
            'view_any_task', 'view_task', 'create_task', 'update_task',
            'view_reports'
        ])->pluck('id')->toArray();
        $managerRole->syncPermissions($managerPermissions);

        // Sales Rep role
        $salesRepRole = Role::firstOrCreate(['name' => 'sales_rep']);
        $salesRepPermissions = Permission::whereIn('name', [
            'view_any_client', 'view_client',
            'view_any_lead', 'view_lead', 'create_lead', 'update_lead',
            'view_any_opportunity', 'view_opportunity', 'create_opportunity', 'update_opportunity',
            'view_any_task', 'view_task', 'create_task', 'update_task'
        ])->pluck('id')->toArray();
        $salesRepRole->syncPermissions($salesRepPermissions);

        // Free role (keeping the existing free role)
        $freeRole = Role::firstOrCreate(['name' => 'free']);
        $freePermissions = Permission::whereIn('name', [
            'view_any_client', 'view_client',
            'view_any_lead', 'view_lead',
            'view_any_opportunity', 'view_opportunity',
            'view_any_task', 'view_task'
        ])->pluck('id')->toArray();
        $freeRole->syncPermissions($freePermissions);
    }
}
