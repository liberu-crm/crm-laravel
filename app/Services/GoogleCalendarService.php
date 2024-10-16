<?php

namespace App\Services;

use App\Models\Task;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class GoogleCalendarService implements CalendarService
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
        $createdEvent = $this->service->events->insert($calendarId, $event);

        $task->google_event_id = $createdEvent->id;
        $task->save();
    }

    public function updateEvent(Task $task)
    {
        $event = $this->service->events->get('primary', $task->google_event_id);

        $event->setSummary($task->name);
        $event->setDescription($task->description);
        $event->setStart(['dateTime' => $task->due_date->toRfc3339String()]);
        $event->setEnd(['dateTime' => $task->due_date->addHour()->toRfc3339String()]);

        $this->service->events->update('primary', $event->getId(), $event);
    }

    public function deleteEvent(Task $task)
    {
        $this->service->events->delete('primary', $task->google_event_id);
        $task->google_event_id = null;
        $task->save();
    }

    public function fetchEvents(array $params = [])
    {
        $optParams = [
            'maxResults' => 100,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),
        ];

        $optParams = array_merge($optParams, $params);

        $results = $this->service->events->listEvents('primary', $optParams);
        return $results->getItems();
    }

    public function syncEvents(array $events)
    {
        foreach ($events as $event) {
            $task = Task::where('google_event_id', $event->id)->first();

            if ($task) {
                // Update existing task
                $task->name = $event->getSummary();
                $task->description = $event->getDescription();
                $task->due_date = $event->getStart()->getDateTime();
                $task->save();
            } else {
                // Create new task
                Task::create([
                    'name' => $event->getSummary(),
                    'description' => $event->getDescription(),
                    'due_date' => $event->getStart()->getDateTime(),
                    'google_event_id' => $event->id,
                ]);
            }
        }
    }
}