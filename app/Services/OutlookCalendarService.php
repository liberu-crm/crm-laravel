<?php

namespace App\Services;

use App\Models\Task;
use DateTime;

class OutlookCalendarService implements CalendarService
{
    protected $graph;

    public function __construct()
    {
        // Graph is initialised lazily or injected via property assignment
    }

    protected function getGraph()
    {
        if ($this->graph === null) {
            $this->graph = new \Microsoft\Graph\Graph();
            $this->graph->setAccessToken($this->getAccessToken());
        }

        return $this->graph;
    }

    protected function getAccessToken(): string
    {
        // Implement OAuth logic to get access token
        return config('services.microsoft.access_token', '');
    }

    public function createEvent(Task $task): void
    {
        $event = new \Microsoft\Graph\Model\Event();
        $event->setSubject($task->name);
        $event->setBody(new \Microsoft\Graph\Model\ItemBody(['content' => $task->description]));
        $event->setStart(new \Microsoft\Graph\Model\DateTimeTimeZone([
            'dateTime' => $task->due_date->format('Y-m-d\TH:i:s'),
            'timeZone' => 'UTC',
        ]));
        $event->setEnd(new \Microsoft\Graph\Model\DateTimeTimeZone([
            'dateTime' => $task->due_date->addHour()->format('Y-m-d\TH:i:s'),
            'timeZone' => 'UTC',
        ]));

        $newEvent = $this->getGraph()
            ->createRequest('POST', '/me/events')
            ->attachBody($event)
            ->setReturnType(\Microsoft\Graph\Model\Event::class)
            ->execute();

        $task->outlook_event_id = $newEvent->getId();
        $task->save();
    }

    public function updateEvent(Task $task): void
    {
        $event = new \Microsoft\Graph\Model\Event();
        $event->setSubject($task->name);
        $event->setBody(new \Microsoft\Graph\Model\ItemBody(['content' => $task->description]));
        $event->setStart(new \Microsoft\Graph\Model\DateTimeTimeZone([
            'dateTime' => $task->due_date->format('Y-m-d\TH:i:s'),
            'timeZone' => 'UTC',
        ]));
        $event->setEnd(new \Microsoft\Graph\Model\DateTimeTimeZone([
            'dateTime' => $task->due_date->addHour()->format('Y-m-d\TH:i:s'),
            'timeZone' => 'UTC',
        ]));

        $this->getGraph()
            ->createRequest('PATCH', '/me/events/' . $task->outlook_event_id)
            ->attachBody($event)
            ->execute();
    }

    public function deleteEvent(Task $task): void
    {
        $this->getGraph()
            ->createRequest('DELETE', '/me/events/' . $task->outlook_event_id)
            ->execute();

        $task->outlook_event_id = null;
        $task->save();
    }

    public function fetchEvents(array $params = []): array
    {
        $queryParams = [
            '$top'     => $params['maxResults'] ?? 100,
            '$orderby' => 'start/dateTime',
            '$filter'  => 'start/dateTime ge ' . date('c'),
        ];

        return $this->getGraph()
            ->createRequest('GET', '/me/events?' . http_build_query($queryParams))
            ->setReturnType(\Microsoft\Graph\Model\Event::class)
            ->execute();
    }

    public function syncEvents(array $events): void
    {
        foreach ($events as $event) {
            $task = Task::where('outlook_event_id', $event->getId())->first();

            if ($task) {
                $task->name        = $event->getSubject();
                $task->description = $event->getBody()->getContent();
                $task->due_date    = new DateTime($event->getStart()->getDateTime());
                $task->save();
            } else {
                Task::create([
                    'name'             => $event->getSubject(),
                    'description'      => $event->getBody()->getContent(),
                    'due_date'         => new DateTime($event->getStart()->getDateTime()),
                    'outlook_event_id' => $event->getId(),
                ]);
            }
        }
    }
}
