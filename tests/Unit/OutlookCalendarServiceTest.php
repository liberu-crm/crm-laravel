<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Services\OutlookCalendarService;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
use Mockery;
use Tests\TestCase;

class OutlookCalendarServiceTest extends TestCase
{
    protected $outlookCalendarService;
    protected $mockGraph;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockGraph = Mockery::mock(Graph::class);
        $this->outlookCalendarService = new OutlookCalendarService();
        $this->outlookCalendarService->graph = $this->mockGraph;
    }

    public function testCreateEvent()
    {
        $task = Task::factory()->create();

        $mockRequest = Mockery::mock();
        $mockRequest->shouldReceive('attachBody')->andReturnSelf();
        $mockRequest->shouldReceive('setReturnType')->andReturnSelf();
        $mockRequest->shouldReceive('execute')->andReturn(new Event(['id' => 'test_event_id']));

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
        $mockEvents = [
            new Event(['id' => 'event1']),
            new Event(['id' => 'event2']),
        ];

        $mockRequest = Mockery::mock();
        $mockRequest->shouldReceive('setReturnType')->andReturnSelf();

        $mockRequest->shouldReceive('execute')->andReturn($mockEvents);

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
        $events = [
            new Event([
                'id' => 'event1',
                'subject' => 'Test Event 1',
                'body' => ['content' => 'Test Description 1'],
                'start' => ['dateTime' => '2023-06-01T10:00:00'],
            ]),
            new Event([
                'id' => 'event2',
                'subject' => 'Test Event 2',
                'body' => ['content' => 'Test Description 2'],
                'start' => ['dateTime' => '2023-06-02T11:00:00'],
            ]),
        ];

        $this->outlookCalendarService->syncEvents($events);

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