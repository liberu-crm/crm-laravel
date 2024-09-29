<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Contact;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Contact $contact)
    {
        return $user->team_id === $contact->team_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Contact $contact)
    {
        return $user->team_id === $contact->team_id;
    }

    public function delete(User $user, Contact $contact)
    {
        return $user->team_id === $contact->team_id;
    }

    public function restore(User $user, Contact $contact)
    {
        return $user->team_id === $contact->team_id;
    }

    public function forceDelete(User $user, Contact $contact)
    {
        return $user->team_id === $contact->team_id;
    }
}