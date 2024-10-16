<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\Contact;
use App\Models\Lead;
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

    public function testCreateTaskAssociatedWithContact()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/tasks', [
            'name' => 'Contact Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Contact Task',
            'contact_id' => $contact->id,
        ]);
    }

    public function testCreateTaskAssociatedWithLead()
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/tasks', [
            'name' => 'Lead Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Lead Task',
            'lead_id' => $lead->id,
        ]);
    }

    public function testTaskAssignment()
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/tasks', [
            'name' => 'Assigned Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'assigned_to' => $assignee->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Assigned Task',
            'assigned_to' => $assignee->id,
        ]);
    }

    public function testReminderNotificationForContactTask()
    {
        Notification::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $task = Task::factory()->create([
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
        ]);

        $this->artisan('tasks:send-reminders');

        Notification::assertSentTo(
            [$contact, $user],
            TaskReminderNotification::class,
            function ($notification, $channels, $notifiable) use ($task) {
                return $notification->task->id === $task->id;
            }
        );
    }

    public function testReminderNotificationForLeadTask()
    {
        Notification::fake();

        $user = User::factory()->create();
        $lead = Lead::factory()->create();
        $task = Task::factory()->create([
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
        ]);

        $this->artisan('tasks:send-reminders');

        Notification::assertSentTo(
            [$lead, $user],
            TaskReminderNotification::class,
            function ($notification, $channels, $notifiable) use ($task) {
                return $notification->task->id === $task->id;
            }
        );
    }

    // ... (existing test methods)

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}