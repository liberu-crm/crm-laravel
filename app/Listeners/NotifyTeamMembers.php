<?php

namespace App\Listeners;

use App\Events\ContactUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyTeamMembers implements ShouldQueue
{
    public function handle(ContactUpdated $event): void
    {
        // Notify team members about the contact update
    }
}
