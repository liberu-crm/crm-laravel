<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use App\Services\GoogleCalendarService;
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
            'sync_to_google_calendar' => true,
        ]);

        $response->assertSessionHasErrors('reminder_date');
        $this->assertDatabaseMissing('tasks', ['name' => 'Test Task']);
    }

    public function testGoogleCalendarSyncFailure()
    {
        $user = User::factory()->create(['google_calendar_token' => json_encode(['access_token' => 'test_token'])]);
        $this->actingAs($user);

        // Mock the GoogleCalendarService
        $mockGoogleCalendarService = Mockery::mock(GoogleCalendarService::class);
        $mockGoogleCalendarService->shouldReceive('syncTask')->andThrow(new \Exception('Google Calendar sync failed'));
        $this->app->instance(GoogleCalendarService::class, $mockGoogleCalendarService);

        $response = $this->post('/tasks', [
            'name' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'sync_to_google_calendar' => true,
        ]);

        $response->assertSessionHasErrors('google_calendar_sync');
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'sync_to_google_calendar' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}