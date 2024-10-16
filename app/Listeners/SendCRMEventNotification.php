<?php

namespace App\Listeners;

use App\Notifications\CRMEventNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCRMEventNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle($event)
    {
        $eventName = class_basename($event);
        $notificationConfig = config("crm.notifications.events.{$eventName}");

        if ($notificationConfig) {
            $users = $this->getUsersToNotify($event);
            foreach ($users as $user) {
                $user->notify(new CRMEventNotification($eventName, $event->toArray()));
            }
        }
    }

    protected function getUsersToNotify($event)
    {
        // Implement logic to determine which users should be notified
        // This could be based on user roles, team membership, or other criteria
        // For now, we'll just notify all users (you should refine this)
        return \App\Models\User::all();
    }
}