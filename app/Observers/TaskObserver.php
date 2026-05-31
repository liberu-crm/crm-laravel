<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\GoogleCalendarService;

class TaskObserver
{
    public function __construct(protected \App\Services\GoogleCalendarService $googleCalendarService)
    {
    }

    public function created(Task $task): void
    {
        if ($task->sync_to_google_calendar) {
            $this->googleCalendarService->createEvent($task);
        }
    }

    public function updated(Task $task): void
    {
        if ($task->sync_to_google_calendar) {
            $this->googleCalendarService->updateEvent($task);
        }
    }

    public function deleted(Task $task): void
    {
        if ($task->sync_to_google_calendar) {
            $this->googleCalendarService->deleteEvent($task);
        }
    }
}
