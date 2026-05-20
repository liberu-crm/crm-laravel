<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    public function addTeamMember(User $user, Team $team): bool
    {
        return $user->ownsTeam($team) ||
               $team->teamInvitations()->where('email', $user->email)->exists();
    }

    public function updateTeamMember(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    public function removeTeamMember(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    public function deleteTeamInvitation(User $user, TeamInvitation $invitation): bool
    {
        return $user->ownsTeam($invitation->team);
    }
}
