<?php

namespace App\Services;

use App\Models\Task;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class GoogleCalendarService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(config('services.google.credentials_path'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR);

        $this->service = new Google_Service_Calendar($this->client);
    }

    public function createEvent(Task $task)
    {
        $event = new Google_Service_Calendar_Event([
            'summary' => $task->name,
            'description' => $task->description,
            'start' => ['dateTime' => $task->due_date->toRfc3339String()],
            'end' => ['dateTime' => $task->due_date->addHour()->toRfc3339String()],
        ]);

        $calendarId = 'primary';
        $this->service->events->insert($calendarId, $event);
    }

    public function updateEvent(Task $task)
    {
        // Implement update logic
    }

    public function deleteEvent(Task $task)
    {
        // Implement delete logic
    }
}