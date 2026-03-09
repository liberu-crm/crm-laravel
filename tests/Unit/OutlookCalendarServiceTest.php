<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Services\OutlookCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OutlookCalendarServiceTest extends TestCase
{
    use RefreshDatabase;
    protected $outlookCalendarService;
    protected $mockGraph;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockGraph = Mockery::mock();
        $this->outlookCalendarService = new OutlookCalendarService();
        $this->outlookCalendarService->graph = $this->mockGraph;
    }

    public function testCreateEvent()
    {
        $task = Task::factory()->create();

        $mockCreatedEvent = Mockery::mock();
        $mockCreatedEvent->shouldReceive('getId')->andReturn('test_event_id');

        $mockRequest = Mockery::mock();
        $mockRequest->shouldReceive('attachBody')->andReturnSelf();
        $mockRequest->shouldReceive('setReturnType')->andReturnSelf();
        $mockRequest->shouldReceive('execute')->andReturn($mockCreatedEvent);

        $this->mockGraph->shouldReceive('createRequest')
            ->with('POST', '/me/events')
            ->andReturn($mockRequest);

        $this->outlookCalendarService->createEvent($task);

        $this->assertEquals('test_event_id', $task->outlook_event_id);
    }

    public function testUpdateEvent()
    {
        $task = Task::factory()->create(['outlook_event_id' => 'existing_event_id']);

        $mockRequest = Mockery::mock();
        $mockRequest->shouldReceive('attachBody')->andReturnSelf();
        $mockRequest->shouldReceive('execute')->andReturn(null);

        $this->mockGraph->shouldReceive('createRequest')
            ->with('PATCH', '/me/events/existing_event_id')
            ->andReturn($mockRequest);

        $this->outlookCalendarService->updateEvent($task);
    }

    public function testDeleteEvent()
    {
        $task = Task::factory()->create(['outlook_event_id' => 'existing_event_id']);

        $mockRequest = Mockery::mock();
        $mockRequest->shouldReceive('execute')->andReturn(null);

        $this->mockGraph->shouldReceive('createRequest')
            ->with('DELETE', '/me/events/existing_event_id')
            ->andReturn($mockRequest);

        $this->outlookCalendarService->deleteEvent($task);

        $this->assertNull($task->outlook_event_id);
    }

    public function testFetchEvents()
    {
        $mockEvent1 = Mockery::mock();
        $mockEvent1->shouldReceive('getId')->andReturn('event1');

        $mockEvent2 = Mockery::mock();
        $mockEvent2->shouldReceive('getId')->andReturn('event2');

        $mockRequest = Mockery::mock();
        $mockRequest->shouldReceive('setReturnType')->andReturnSelf();
        $mockRequest->shouldReceive('execute')->andReturn([$mockEvent1, $mockEvent2]);

        $this->mockGraph->shouldReceive('createRequest')
            ->with('GET', Mockery::type('string'))
            ->andReturn($mockRequest);

        $events = $this->outlookCalendarService->fetchEvents();

        $this->assertCount(2, $events);
        $this->assertEquals('event1', $events[0]->getId());
        $this->assertEquals('event2', $events[1]->getId());
    }

    public function testSyncEvents()
    {
        $mockBody1 = Mockery::mock();
        $mockBody1->shouldReceive('getContent')->andReturn('Test Description 1');

        $mockStart1 = Mockery::mock();
        $mockStart1->shouldReceive('getDateTime')->andReturn('2023-06-01T10:00:00');

        $mockEvent1 = Mockery::mock();
        $mockEvent1->shouldReceive('getId')->andReturn('event1');
        $mockEvent1->shouldReceive('getSubject')->andReturn('Test Event 1');
        $mockEvent1->shouldReceive('getBody')->andReturn($mockBody1);
        $mockEvent1->shouldReceive('getStart')->andReturn($mockStart1);

        $mockBody2 = Mockery::mock();
        $mockBody2->shouldReceive('getContent')->andReturn('Test Description 2');

        $mockStart2 = Mockery::mock();
        $mockStart2->shouldReceive('getDateTime')->andReturn('2023-06-02T11:00:00');

        $mockEvent2 = Mockery::mock();
        $mockEvent2->shouldReceive('getId')->andReturn('event2');
        $mockEvent2->shouldReceive('getSubject')->andReturn('Test Event 2');
        $mockEvent2->shouldReceive('getBody')->andReturn($mockBody2);
        $mockEvent2->shouldReceive('getStart')->andReturn($mockStart2);

        $this->outlookCalendarService->syncEvents([$mockEvent1, $mockEvent2]);

        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Event 1',
            'description' => 'Test Description 1',
            'outlook_event_id' => 'event1',
        ]);

        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Event 2',
            'description' => 'Test Description 2',
            'outlook_event_id' => 'event2',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
