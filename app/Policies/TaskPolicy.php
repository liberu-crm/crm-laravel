<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $task->belongsToTeam($user->currentTeam?->getKey());
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $task->belongsToTeam($user->currentTeam?->getKey());
    }

    public function delete(User $user, Task $task): bool
    {
        return $task->belongsToTeam($user->currentTeam?->getKey());
    }

    public function restore(User $user, Task $task): bool
    {
        return $task->belongsToTeam($user->currentTeam?->getKey());
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $task->belongsToTeam($user->currentTeam?->getKey());
    }
}
