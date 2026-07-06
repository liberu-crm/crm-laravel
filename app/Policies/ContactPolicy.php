<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contact $contact): bool
    {
        return $contact->belongsToTeam($user->currentTeam?->getKey());
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Contact $contact): bool
    {
        return $contact->belongsToTeam($user->currentTeam?->getKey());
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $contact->belongsToTeam($user->currentTeam?->getKey());
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $contact->belongsToTeam($user->currentTeam?->getKey());
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $contact->belongsToTeam($user->currentTeam?->getKey());
    }
}
