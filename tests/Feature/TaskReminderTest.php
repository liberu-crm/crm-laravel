<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskReminderTest extends TestCase
{
    use RefreshDatabase;

    public function testTaskCreationWithReminder()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/tasks', [
            'name' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'reminder_date' => now()->addDay(),
            'sync_to_google_calendar' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'reminder_date' => now()->addDay()->toDateTimeString(),
            'reminder_sent' => false,
        ]);
    }

    public function testSendRemindersCommand()
    {
        Notification::fake();

        $user = User::factory()->create();
        $task = Task::factory()->create([
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
            'contact_id' => $user->id,
        ]);

        $this->artisan('reminders:send')->assertSuccessful();

        Notification::assertSentTo($user, TaskReminderNotification::class);
        $this->assertTrue($task->fresh()->reminder_sent);
    }

    public function testGoogleCalendarSync()
    {
        $user = User::factory()->create(['google_calendar_token' => json_encode(['access_token' => 'test_token'])]);
        $this->actingAs($user);

        $response = $this->post('/tasks', [
            'name' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'sync_to_google_calendar' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'sync_to_google_calendar' => true,
        ]);

        // Note: In a real scenario, you would mock the Google Calendar API calls
        // and assert that the correct methods were called with the right parameters.
    }
}