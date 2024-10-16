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

    protected $googleCalendarService;
    protected $outlookCalendarService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->googleCalendarService = Mockery::mock(GoogleCalendarService::class);
        $this->outlookCalendarService = Mockery::mock(OutlookCalendarService::class);
        $this->app->instance(GoogleCalendarService::class, $this->googleCalendarService);
        $this->app->instance(OutlookCalendarService::class, $this->outlookCalendarService);
    }

    public function testCreateTaskAssociatedWithContact()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $this->actingAs($user);

        $this->googleCalendarService->shouldReceive('createEvent')->once()->andReturn(true);

        $response = $this->post('/tasks', [
            'name' => 'Contact Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
            'calendar_type' => 'google',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Contact Task',
            'contact_id' => $contact->id,
            'calendar_type' => 'google',
        ]);
    }

    public function testCreateTaskAssociatedWithLead()
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create();
        $this->actingAs($user);

        $this->outlookCalendarService->shouldReceive('createEvent')->once()->andReturn(true);

        $response = $this->post('/tasks', [
            'name' => 'Lead Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
            'calendar_type' => 'outlook',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Lead Task',
            'lead_id' => $lead->id,
            'calendar_type' => 'outlook',
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

        $this->assertTrue($task->fresh()->reminder_sent);
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

        $this->assertTrue($task->fresh()->reminder_sent);
    }

    public function testNoReminderSentForFutureTask()
    {
        Notification::fake();

        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'reminder_date' => now()->addDays(1),
            'reminder_sent' => false,
        ]);

        $this->artisan('tasks:send-reminders');

        Notification::assertNothingSent();
        $this->assertFalse($task->fresh()->reminder_sent);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}