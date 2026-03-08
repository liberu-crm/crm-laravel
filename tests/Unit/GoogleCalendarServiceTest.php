<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Services\GoogleCalendarService;
use Mockery;
use Tests\TestCase;

class GoogleCalendarServiceTest extends TestCase
{
    protected $googleCalendarService;
    protected $mockGoogleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockGoogleService = Mockery::mock();
        $this->googleCalendarService = new GoogleCalendarService();
        $this->googleCalendarService->service = $this->mockGoogleService;
    }

    public function testCreateEvent()
    {
        $task = Task::factory()->create();

        $mockCreatedEvent = Mockery::mock();
        $mockCreatedEvent->id = 'test_event_id';

        $this->mockGoogleService->events = Mockery::mock();
        $this->mockGoogleService->events->shouldReceive('insert')
            ->once()
            ->with('primary', Mockery::any())
            ->andReturn($mockCreatedEvent);

        $this->googleCalendarService->createEvent($task);

        $this->assertEquals('test_event_id', $task->google_event_id);
    }

    public function testUpdateEvent()
    {
        $task = Task::factory()->create(['google_event_id' => 'existing_event_id']);

        $mockEvent = Mockery::mock();
        $mockEvent->shouldReceive('setSummary')->andReturnSelf();
        $mockEvent->shouldReceive('setDescription')->andReturnSelf();
        $mockEvent->shouldReceive('setStart')->andReturnSelf();
        $mockEvent->shouldReceive('setEnd')->andReturnSelf();

        $this->mockGoogleService->events = Mockery::mock();
        $this->mockGoogleService->events->shouldReceive('get')
            ->once()
            ->with('primary', 'existing_event_id')
            ->andReturn($mockEvent);

        $this->mockGoogleService->events->shouldReceive('update')
            ->once()
            ->with('primary', 'existing_event_id', Mockery::any())
            ->andReturn($mockEvent);

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
        $mockEvent1 = Mockery::mock();
        $mockEvent1->id = 'event1';
        $mockEvent1->shouldReceive('getId')->andReturn('event1');

        $mockEvent2 = Mockery::mock();
        $mockEvent2->id = 'event2';
        $mockEvent2->shouldReceive('getId')->andReturn('event2');

        $mockResults = Mockery::mock();
        $mockResults->shouldReceive('getItems')->andReturn([$mockEvent1, $mockEvent2]);

        $this->mockGoogleService->events = Mockery::mock();
        $this->mockGoogleService->events->shouldReceive('listEvents')
            ->once()
            ->with('primary', Mockery::type('array'))
            ->andReturn($mockResults);

        $events = $this->googleCalendarService->fetchEvents();

        $this->assertCount(2, $events);
        $this->assertEquals('event1', $events[0]->getId());
        $this->assertEquals('event2', $events[1]->getId());
    }

    public function testSyncEvents()
    {
        $mockStart1 = Mockery::mock();
        $mockStart1->shouldReceive('getDateTime')->andReturn('2023-06-01 10:00:00');

        $mockEvent1 = Mockery::mock();
        $mockEvent1->id = 'event1';
        $mockEvent1->shouldReceive('getSummary')->andReturn('Test Event 1');
        $mockEvent1->shouldReceive('getDescription')->andReturn('Test Description 1');
        $mockEvent1->shouldReceive('getStart')->andReturn($mockStart1);

        $mockStart2 = Mockery::mock();
        $mockStart2->shouldReceive('getDateTime')->andReturn('2023-06-02 11:00:00');

        $mockEvent2 = Mockery::mock();
        $mockEvent2->id = 'event2';
        $mockEvent2->shouldReceive('getSummary')->andReturn('Test Event 2');
        $mockEvent2->shouldReceive('getDescription')->andReturn('Test Description 2');
        $mockEvent2->shouldReceive('getStart')->andReturn($mockStart2);

        $this->googleCalendarService->syncEvents([$mockEvent1, $mockEvent2]);

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
