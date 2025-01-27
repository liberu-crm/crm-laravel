<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');
        $this->createTeamForUser($adminUser);
        
        $managerUser = User::create([
            'name' => 'manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $managerUser->assignRole('manager');
        $this->createTeamForUser($managerUser);
    }

    private function createTeamForUser($user)
    {
        $team = Team::first();
        $team->users()->attach($user);
        $user->current_team_id = 1;
        $user->save();
    }
}
