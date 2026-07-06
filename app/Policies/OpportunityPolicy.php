<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OpportunityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        return $opportunity->belongsToTeam($user->currentTeam?->getKey());
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        return $opportunity->belongsToTeam($user->currentTeam?->getKey());
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        return $opportunity->belongsToTeam($user->currentTeam?->getKey());
    }

    public function restore(User $user, Opportunity $opportunity): bool
    {
        return $opportunity->belongsToTeam($user->currentTeam?->getKey());
    }

    public function forceDelete(User $user, Opportunity $opportunity): bool
    {
        return $opportunity->belongsToTeam($user->currentTeam?->getKey());
    }
}
