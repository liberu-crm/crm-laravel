<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use BezhanSalleh\FilamentShield\Support\Utils;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleNames = ['super_admin', 'admin', 'manager', 'sales_rep'];

        foreach ($roleNames as $roleName) {
            $roleData = [
                'name' => $roleName,
                'guard_name' => 'web',
            ];

            if (Utils::isTenancyEnabled()) {
                $team = Team::firstOrFail();
                $roleData["team_id"] = $team->id;
            }

            $role = Role::firstOrCreate($roleData);

            if ($roleName === 'super_admin') {
                $permissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
                $role->syncPermissions($permissions);
            }
        }
    }
}
