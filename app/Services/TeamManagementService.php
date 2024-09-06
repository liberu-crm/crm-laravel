<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TeamManagementService
{
    public function createDefaultTeamForUser(User $user): Team
    {
        try {
            $defaultBranch = Branch::firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new \Exception('No default branch found. Please set up at least one branch.');
        }

        return $user->ownedTeams()->create([
            'name' => $defaultBranch->name . ' Team',
            'personal_team' => false,
            'branch_id' => $defaultBranch->id,
        ]);
    }

    public function createPersonalTeamForUser(User $user): Team
    {
        return $user->ownedTeams()->create([
            'name' => $user->name . "'s Team",
            'personal_team' => true,
        ]);
    }

    public function assignUserToDefaultTeam(User $user): void
    {
        $defaultTeam = Team::where('personal_team', false)->first();

        if (!$defaultTeam) {
            $defaultTeam = $this->createDefaultTeamForUser($user);
        }

        $this->assignUserToTeam($user, $defaultTeam);
    }

    public function assignUserToTeam(User $user, Team $team): void
    {
        if (!$user->belongsToTeam($team)) {
            $user->teams()->attach($team, ['role' => 'member']);
        }
        $user->switchTeam($team);
    }

    public function switchTeam(User $user, Team $team): void
    {
        if (!$user->belongsToTeam($team)) {
            throw new \Exception('User does not belong to the specified team.');
        }
        $user->switchTeam($team);
    }
}