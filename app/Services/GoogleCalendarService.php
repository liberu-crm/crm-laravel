<?php

namespace App\Services;

use App\Models\Task;

class GoogleCalendarService implements CalendarService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        // Client and service are initialised lazily or injected via property assignment
    }

    protected function getService()
    {
        if ($this->service === null) {
            $client = new \Google_Client();
            $client->setAuthConfig(config('services.google.credentials_path'));
            $client->addScope(\Google_Service_Calendar::CALENDAR);
            $this->service = new \Google_Service_Calendar($client);
        }

        return $this->service;
    }

    public function createEvent(Task $task): void
    {
        $event = new \Google_Service_Calendar_Event([
            'summary'     => $task->name,
            'description' => $task->description,
            'start'       => ['dateTime' => $task->due_date->toRfc3339String()],
            'end'         => ['dateTime' => $task->due_date->addHour()->toRfc3339String()],
        ]);

        $createdEvent = $this->getService()->events->insert('primary', $event);

        $task->google_event_id = $createdEvent->id;
        $task->save();
    }

    public function updateEvent(Task $task): void
    {
        $event = $this->getService()->events->get('primary', $task->google_event_id);

        $event->setSummary($task->name);
        $event->setDescription($task->description);
        $event->setStart(['dateTime' => $task->due_date->toRfc3339String()]);
        $event->setEnd(['dateTime' => $task->due_date->addHour()->toRfc3339String()]);

        $this->getService()->events->update('primary', $task->google_event_id, $event);
    }

    public function deleteEvent(Task $task): void
    {
        $this->getService()->events->delete('primary', $task->google_event_id);
        $task->google_event_id = null;
        $task->save();
    }

    public function fetchEvents(array $params = []): array
    {
        $optParams = array_merge([
            'maxResults'   => 100,
            'orderBy'      => 'startTime',
            'singleEvents' => true,
            'timeMin'      => date('c'),
        ], $params);

        $results = $this->getService()->events->listEvents('primary', $optParams);
        return $results->getItems();
    }

    public function syncEvents(array $events): void
    {
        foreach ($events as $event) {
            $task = Task::where('google_event_id', $event->id)->first();

            if ($task) {
                $task->name        = $event->getSummary();
                $task->description = $event->getDescription();
                $task->due_date    = $event->getStart()->getDateTime();
                $task->save();
            } else {
                Task::create([
                    'name'            => $event->getSummary(),
                    'description'     => $event->getDescription(),
                    'due_date'        => $event->getStart()->getDateTime(),
                    'google_event_id' => $event->id,
                ]);
            }
        }
    }
}
