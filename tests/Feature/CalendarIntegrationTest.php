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

    public function testCreateTaskAndSyncWithGoogleCalendar()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mockGoogleCalendarService->shouldReceive('createEvent')->once()->andReturn('google_event_id');

        $response = $this->post('/tasks', [
            'name' => 'Test Google Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'calendar_type' => 'google',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Google Task',
            'calendar_type' => 'google',
            'calendar_event_id' => 'google_event_id',
        ]);
    }

    public function testCreateTaskAndSyncWithOutlookCalendar()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mockOutlookCalendarService->shouldReceive('createEvent')->once()->andReturn('outlook_event_id');

        $response = $this->post('/tasks', [
            'name' => 'Test Outlook Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'calendar_type' => 'outlook',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Outlook Task',
            'calendar_type' => 'outlook',
            'calendar_event_id' => 'outlook_event_id',
        ]);
    }

    public function testUpdateTaskAndSyncWithGoogleCalendar()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'calendar_type' => 'google',
            'calendar_event_id' => 'existing_google_event_id',
        ]);

        $this->mockGoogleCalendarService->shouldReceive('updateEvent')->once()->with('existing_google_event_id', Mockery::any())->andReturn(true);

        $response = $this->patch("/tasks/{$task->id}", [
            'name' => 'Updated Google Task',
            'description' => 'Updated Description',
            'due_date' => now()->addDays(3),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Updated Google Task',
            'calendar_type' => 'google',
            'calendar_event_id' => 'existing_google_event_id',
        ]);
    }

    public function testUpdateTaskAndSyncWithOutlookCalendar()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'calendar_type' => 'outlook',
            'calendar_event_id' => 'existing_outlook_event_id',
        ]);

        $this->mockOutlookCalendarService->shouldReceive('updateEvent')->once()->with('existing_outlook_event_id', Mockery::any())->andReturn(true);

        $response = $this->patch("/tasks/{$task->id}", [
            'name' => 'Updated Outlook Task',
            'description' => 'Updated Description',
            'due_date' => now()->addDays(3),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Updated Outlook Task',
            'calendar_type' => 'outlook',
            'calendar_event_id' => 'existing_outlook_event_id',
        ]);
    }

    public function testDeleteTaskAndRemoveFromGoogleCalendar()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'calendar_type' => 'google',
            'calendar_event_id' => 'google_event_id_to_delete',
        ]);

        $this->mockGoogleCalendarService->shouldReceive('deleteEvent')->once()->with('google_event_id_to_delete')->andReturn(true);

        $response = $this->delete("/tasks/{$task->id}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function testDeleteTaskAndRemoveFromOutlookCalendar()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'calendar_type' => 'outlook',
            'calendar_event_id' => 'outlook_event_id_to_delete',
        ]);

        $this->mockOutlookCalendarService->shouldReceive('deleteEvent')->once()->with('outlook_event_id_to_delete')->andReturn(true);

        $response = $this->delete("/tasks/{$task->id}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function testSwitchCalendarType()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'calendar_type' => 'google',
            'calendar_event_id' => 'old_google_event_id',
        ]);

        $this->mockGoogleCalendarService->shouldReceive('deleteEvent')->once()->with('old_google_event_id')->andReturn(true);
        $this->mockOutlookCalendarService->shouldReceive('createEvent')->once()->andReturn('new_outlook_event_id');

        $response = $this->patch("/tasks/{$task->id}", [
            'calendar_type' => 'outlook',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'calendar_type' => 'outlook',
            'calendar_event_id' => 'new_outlook_event_id',
        ]);
    }

    public function testHandleCalendarSyncFailure()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mockGoogleCalendarService->shouldReceive('createEvent')->once()->andThrow(new \Exception('Failed to sync with Google Calendar'));

        $response = $this->post('/tasks', [
            'name' => 'Test Google Task',

            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'calendar_type' => 'google',
        ]);

        $response->assertSessionHasErrors(['calendar_sync' => 'Failed to sync task with Google Calendar']);
        $this->assertDatabaseMissing('tasks', ['name' => 'Test Google Task']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}