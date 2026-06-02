<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Team;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Role::cases() as $roleEnum) {
            $roleData = [
                'name' => $roleEnum->value,
                'guard_name' => 'web',
            ];

            if (Utils::isTenancyEnabled()) {
                $team = Team::firstOrFail();
                $roleData['team_id'] = $team->id;
            }

            $role = SpatieRole::firstOrCreate($roleData);

            if ($roleEnum === Role::SuperAdmin) {
                $permissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
                $role->syncPermissions($permissions);
            }
        }
    }
}
