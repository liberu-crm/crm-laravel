<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use App\Services\GoogleCalendarService;
use App\Services\OutlookCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class TaskReminderTest extends TestCase
{
    use RefreshDatabase;

    // ... (existing test methods)

    public function testTaskCreationWithInvalidReminderDate()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/tasks', [
            'name' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'reminder_date' => now()->subDay(), // Invalid reminder date (in the past)
            'calendar_type' => 'google',
        ]);

        $response->assertSessionHasErrors('reminder_date');
        $this->assertDatabaseMissing('tasks', ['name' => 'Test Task']);
    }

    public function testGoogleCalendarSyncFailure()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Mock the GoogleCalendarService
        $mockGoogleCalendarService = Mockery::mock(GoogleCalendarService::class);
        $mockGoogleCalendarService->shouldReceive('createEvent')->andThrow(new \Exception('Google Calendar sync failed'));
        $this->app->instance(GoogleCalendarService::class, $mockGoogleCalendarService);

        $response = $this->post('/tasks', [
            'name' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'calendar_type' => 'google',
        ]);

        $response->assertSessionHasErrors('calendar_sync');
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'calendar_type' => 'none',
        ]);
    }

    public function testOutlookCalendarSyncFailure()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Mock the OutlookCalendarService
        $mockOutlookCalendarService = Mockery::mock(OutlookCalendarService::class);
        $mockOutlookCalendarService->shouldReceive('createEvent')->andThrow(new \Exception('Outlook Calendar sync failed'));
        $this->app->instance(OutlookCalendarService::class, $mockOutlookCalendarService);

        $response = $this->post('/tasks', [
            'name' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'calendar_type' => 'outlook',
        ]);

        $response->assertSessionHasErrors('calendar_sync');
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'calendar_type' => 'none',
        ]);
    }

    public function testSwitchingBetweenCalendarServices()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a task with Google Calendar
        $response = $this->post('/tasks', [
            'name' => 'Google Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'calendar_type' => 'google',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Google Task',
            'calendar_type' => 'google',
        ]);

        $task = Task::where('name', 'Google Task')->first();

        // Switch to Outlook Calendar
        $response = $this->patch("/tasks/{$task->id}", [
            'name' => 'Outlook Task',
            'calendar_type' => 'outlook',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Outlook Task',
            'calendar_type' => 'outlook',
        ]);

        // Switch back to no calendar sync
        $response = $this->patch("/tasks/{$task->id}", [
            'name' => 'No Sync Task',
            'calendar_type' => 'none',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'No Sync Task',
            'calendar_type' => 'none',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}