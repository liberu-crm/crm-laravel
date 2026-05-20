<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\OutlookCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockGoogleCalendarService = Mockery::mock(GoogleCalendarService::class);
        $this->app->instance(GoogleCalendarService::class, $this->mockGoogleCalendarService);

        $this->mockOutlookCalendarService = Mockery::mock(OutlookCalendarService::class);
        $this->app->instance(OutlookCalendarService::class, $this->mockOutlookCalendarService);
    }

    public function testTaskCanBeCreatedWithGoogleCalendarType()
    {
        $user = User::factory()->create();

        $this->mockGoogleCalendarService->shouldReceive('createEvent')
            ->once()
            ->andReturn('google_event_id');

        $task = Task::factory()->create([
            'name' => 'Test Google Task',
            'calendar_type' => 'google',
            'google_event_id' => app(GoogleCalendarService::class)->createEvent(new Task()),
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Test Google Task',
            'calendar_type' => 'google',
        ]);
    }

    public function testTaskCanBeCreatedWithOutlookCalendarType()
    {
        $user = User::factory()->create();

        $this->mockOutlookCalendarService->shouldReceive('createEvent')
            ->once()
            ->andReturn('outlook_event_id');

        $task = Task::factory()->create([
            'name' => 'Test Outlook Task',
            'calendar_type' => 'outlook',
            'outlook_event_id' => app(OutlookCalendarService::class)->createEvent(new Task()),
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Test Outlook Task',
            'calendar_type' => 'outlook',
        ]);
    }

    public function testTaskHasCalendarFields()
    {
        $task = Task::factory()->create([
            'calendar_type' => 'google',
            'google_event_id' => 'google_event_123',
        ]);

        $this->assertEquals('google', $task->calendar_type);
        $this->assertEquals('google_event_123', $task->google_event_id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
