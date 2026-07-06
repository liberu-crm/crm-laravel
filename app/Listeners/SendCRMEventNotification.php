<?php

namespace App\Listeners;

use App\Notifications\CRMEventNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

class SendCRMEventNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle($event): void
    {
        // config/crm.php keys are snake_case (new_lead); class_basename is StudlyCase
        // (NewLead). Without snake() the lookup always missed and nothing notified.
        $eventName = Str::snake(class_basename($event));
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
        // Anti-leak: notify only the team that owns the event's model, never
        // every user (User::all() was a cross-tenant notification leak). Each
        // CRM event exposes its team via team().
        $team = method_exists($event, 'team') ? $event->team() : null;

        return $team ? $team->allUsers() : collect();
    }
}
