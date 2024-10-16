<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Services\GoogleCalendarService;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_Events;
use Mockery;
use Tests\TestCase;

class GoogleCalendarServiceTest extends TestCase
{
    protected $googleCalendarService;
    protected $mockGoogleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockGoogleService = Mockery::mock(Google_Service_Calendar::class);
        $this->googleCalendarService = new GoogleCalendarService();
        $this->googleCalendarService->service = $this->mockGoogleService;
    }

    public function testCreateEvent()
    {
        $task = Task::factory()->create();

        $this->mockGoogleService->events = Mockery::mock();
        $this->mockGoogleService->events->shouldReceive('insert')
            ->once()
            ->with('primary', Mockery::type(Google_Service_Calendar_Event::class))
            ->andReturn(new Google_Service_Calendar_Event(['id' => 'test_event_id']));

        $this->googleCalendarService->createEvent($task);

        $this->assertEquals('test_event_id', $task->google_event_id);
    }

    public function testUpdateEvent()
    {
        $task = Task::factory()->create(['google_event_id' => 'existing_event_id']);

        $this->mockGoogleService->events = Mockery::mock();
        $this->mockGoogleService->events->shouldReceive('get')
            ->once()
            ->with('primary', 'existing_event_id')
            ->andReturn(new Google_Service_Calendar_Event());

        $this->mockGoogleService->events->shouldReceive('update')
            ->once()
            ->with('primary', 'existing_event_id', Mockery::type(Google_Service_Calendar_Event::class))
            ->andReturn(new Google_Service_Calendar_Event());

        $this->googleCalendarService->updateEvent($task);
    }

    public function testDeleteEvent()
    {
        $task = Task::factory()->create(['google_event_id' => 'existing_event_id']);

        $this->mockGoogleService->events = Mockery::mock();
        $this->mockGoogleService->events->shouldReceive('delete')
            ->once()
            ->with('primary', 'existing_event_id');

        $this->googleCalendarService->deleteEvent($task);

        $this->assertNull($task->google_event_id);
    }

    public function testFetchEvents()
    {
        $mockEvents = new Google_Service_Calendar_Events([
            'items' => [
                new Google_Service_Calendar_Event(['id' => 'event1']),
                new Google_Service_Calendar_Event(['id' => 'event2']),
            ]
        ]);

        $this->mockGoogleService->events = Mockery::mock();
        $this->mockGoogleService->events->shouldReceive('listEvents')
            ->once()
            ->with('primary', Mockery::type('array'))
            ->andReturn($mockEvents);

        $events = $this->googleCalendarService->fetchEvents();

        $this->assertCount(2, $events);
        $this->assertEquals('event1', $events[0]->getId());
        $this->assertEquals('event2', $events[1]->getId());
    }

    public function testSyncEvents()
    {
        $events = [
            new Google_Service_Calendar_Event([
                'id' => 'event1',
                'summary' => 'Test Event 1',
                'description' => 'Test Description 1',
                'start' => ['dateTime' => '2023-06-01T10:00:00+00:00'],
            ]),
            new Google_Service_Calendar_Event([
                'id' => 'event2',
                'summary' => 'Test Event 2',
                'description' => 'Test Description 2',
                'start' => ['dateTime' => '2023-06-02T11:00:00+00:00'],
            ]),
        ];

        $this->googleCalendarService->syncEvents($events);

        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Event 1',
            'description' => 'Test Description 1',
            'google_event_id' => 'event1',
        ]);

        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Event 2',
            'description' => 'Test Description 2',
            'google_event_id' => 'event2',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

namespace Tests\Unit;

use App\Models\Task;
use App\Services\GoogleCalendarService;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Mockery;
use Tests\TestCase;

class GoogleCalendarServiceTest extends TestCase
{
    protected $googleCalendarService;
    protected $mockGoogleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockGoogleService = Mockery::mock(Google_Service_Calendar::class);
        $this->googleCalendarService = new GoogleCalendarService();
        $this->googleCalendarService->service = $this->mockGoogleService;
    }

    public function testCreateEvent()
    {
        $task = Task::factory()->create();

        $this->mockGoogleService->events = Mockery::mock();
        $this->mockGoogleService->events->shouldReceive('insert')
            ->once()
            ->with('primary', Mockery::type(Google_Service_Calendar_Event::class))
            ->andReturn(new Google_Service_Calendar_Event());

        $this->googleCalendarService->createEvent($task);
    }

    // Add more tests for updateEvent and deleteEvent methods

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}