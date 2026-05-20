<?php

namespace App\Services;

use App\Models\Task;

interface CalendarService
{
    public function createEvent(Task $task);
    public function updateEvent(Task $task);
    public function deleteEvent(Task $task);
    public function fetchEvents(array $params = []);
    public function syncEvents(array $events);
}