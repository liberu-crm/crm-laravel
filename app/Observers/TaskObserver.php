<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\GoogleCalendarService;

class TaskObserver
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    public function created(Task $task)
    {
        if ($task->sync_to_google_calendar) {
            $this->googleCalendarService->createEvent($task);
        }
    }

    public function updated(Task $task)
    {
        if ($task->sync_to_google_calendar) {
            $this->googleCalendarService->updateEvent($task);
        }
    }

    public function deleted(Task $task)
    {
        if ($task->sync_to_google_calendar) {
            $this->googleCalendarService->deleteEvent($task);
        }
    }
}