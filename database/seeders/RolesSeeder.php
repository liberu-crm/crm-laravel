<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Role definitions are global: one row per role with team_id = null,
        // usable in every team. Per-team scoping lives on the *assignment*
        // (model_has_roles.team_id), not on the definition.
        foreach (Role::cases() as $roleEnum) {
            $roleData = [
                'name' => $roleEnum->value,
                'guard_name' => 'web',
                'team_id' => null,
            ];

            $role = SpatieRole::firstOrCreate($roleData);

            if ($roleEnum === Role::SuperAdmin) {
                $permissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
                $role->syncPermissions($permissions);
            }
        }
    }
}
