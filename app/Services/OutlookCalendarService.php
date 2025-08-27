<?php

namespace App\Services;

use Microsoft\Graph\Model\Event;
use Microsoft\Graph\Model\ItemBody;
use Microsoft\Graph\Model\DateTimeTimeZone;
use DateTime;
use App\Models\Task;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;

class OutlookCalendarService implements CalendarService
{
    protected $graph;

    public function __construct()
    {
        $this->graph = new Graph();
        $this->graph->setAccessToken($this->getAccessToken());
    }

    protected function getAccessToken()
    {
        // Implement OAuth logic to get access token
        // This is a placeholder and should be replaced with actual OAuth implementation
        return 'access_token';
    }

    public function createEvent(Task $task)
    {
        $event = new Event();
        $event->setSubject($task->name);
        $event->setBody(new ItemBody(['content' => $task->description]));
        $event->setStart(new DateTimeTimeZone(['dateTime' => $task->due_date->format('Y-m-d\TH:i:s'), 'timeZone' => 'UTC']));
        $event->setEnd(new DateTimeTimeZone(['dateTime' => $task->due_date->addHour()->format('Y-m-d\TH:i:s'), 'timeZone' => 'UTC']));

        $newEvent = $this->graph->createRequest('POST', '/me/events')
            ->attachBody($event)
            ->setReturnType(Event::class)
            ->execute();

        $task->outlook_event_id = $newEvent->getId();
        $task->save();
    }

    public function updateEvent(Task $task)
    {
        $event = new Event();
        $event->setSubject($task->name);
        $event->setBody(new ItemBody(['content' => $task->description]));
        $event->setStart(new DateTimeTimeZone(['dateTime' => $task->due_date->format('Y-m-d\TH:i:s'), 'timeZone' => 'UTC']));
        $event->setEnd(new DateTimeTimeZone(['dateTime' => $task->due_date->addHour()->format('Y-m-d\TH:i:s'), 'timeZone' => 'UTC']));

        $this->graph->createRequest('PATCH', '/me/events/' . $task->outlook_event_id)
            ->attachBody($event)
            ->execute();
    }

    public function deleteEvent(Task $task)
    {
        $this->graph->createRequest('DELETE', '/me/events/' . $task->outlook_event_id)
            ->execute();

        $task->outlook_event_id = null;
        $task->save();
    }

    public function fetchEvents(array $params = [])
    {
        $queryParams = [
            '$top' => $params['maxResults'] ?? 100,
            '$orderby' => 'start/dateTime',
            '$filter' => 'start/dateTime ge ' . date('c'),
        ];

        $events = $this->graph->createRequest('GET', '/me/events?' . http_build_query($queryParams))
            ->setReturnType(Event::class)
            ->execute();

        return $events;
    }

    public function syncEvents(array $events)
    {
        foreach ($events as $event) {
            $task = Task::where('outlook_event_id', $event->getId())->first();

            if ($task) {
                // Update existing task
                $task->name = $event->getSubject();
                $task->description = $event->getBody()->getContent();
                $task->due_date = new DateTime($event->getStart()->getDateTime());
                $task->save();
            } else {
                // Create new task
                Task::create([
                    'name' => $event->getSubject(),
                    'description' => $event->getBody()->getContent(),
                    'due_date' => new DateTime($event->getStart()->getDateTime()),
                    'outlook_event_id' => $event->getId(),
                ]);
            }
        }
    }
}