<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Role as SpatieRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = Str::random(12);
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make($adminPassword),
            'email_verified_at' => now(),
        ]);

        $team = Team::firstOrFail();
        $adminUser->teams()->syncWithoutDetaching([$team->id]);

        $role = SpatieRole::where('name', Role::SuperAdmin->value)->firstOrFail();
        $adminUser->assignRole($role);

        echo "Admin password: {$adminPassword}\n";
    }
}
