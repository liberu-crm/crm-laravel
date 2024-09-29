<?php

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