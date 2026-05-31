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
        return $user->team_id === $contact->team_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->team_id === $contact->team_id;
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->team_id === $contact->team_id;
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $user->team_id === $contact->team_id;
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->team_id === $contact->team_id;
    }
}
