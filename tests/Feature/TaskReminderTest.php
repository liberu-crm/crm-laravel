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

    public function testCreateTaskWithGoogleCalendar()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->googleCalendarService->shouldReceive('createEvent')
            ->once()
            ->andReturn('google_event_id_123');

        $task = Task::factory()->create([
            'name' => 'Contact Task',
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
            'calendar_type' => 'google',
            'google_event_id' => app(GoogleCalendarService::class)->createEvent([]),
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Contact Task',
            'contact_id' => $contact->id,
            'calendar_type' => 'google',
        ]);
    }

    public function testTaskReminderNotificationIsSent()
    {
        Notification::fake();

        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
        ]);

        $user->notify(new TaskReminderNotification($task));

        Notification::assertSentTo($user, TaskReminderNotification::class);
    }

    public function testTaskHasReminderDate()
    {
        $task = Task::factory()->withReminder()->create();

        $this->assertNotNull($task->reminder_date);
        $this->assertFalse($task->reminder_sent);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
