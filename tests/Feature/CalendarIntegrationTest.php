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

        $this->mockGoogleCalendarService->shouldReceive('createEvent')->once()->andReturn(true);

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
        ]);
    }

    public function testCreateTaskAndSyncWithOutlookCalendar()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mockOutlookCalendarService->shouldReceive('createEvent')->once()->andReturn(true);

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
        ]);
    }

    public function testUpdateTaskAndSyncWithCalendar()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create([
            'user_id' => $user->id,

            'calendar_type' => 'google',
        ]);

        $this->mockGoogleCalendarService->shouldReceive('updateEvent')->once()->andReturn(true);

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
        ]);
    }

    public function testDeleteTaskAndRemoveFromCalendar()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'calendar_type' => 'outlook',
        ]);

        $this->mockOutlookCalendarService->shouldReceive('deleteEvent')->once()->andReturn(true);

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
        ]);

        $this->mockGoogleCalendarService->shouldReceive('deleteEvent')->once()->andReturn(true);
        $this->mockOutlookCalendarService->shouldReceive('createEvent')->once()->andReturn(true);

        $response = $this->patch("/tasks/{$task->id}", [
            'calendar_type' => 'outlook',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'calendar_type' => 'outlook',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}