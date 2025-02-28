<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Models\Contact;
use App\Models\Lead;
use App\Notifications\TaskReminderNotification;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $reminderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reminderService = new ReminderService();
        restore_error_handler();
        restore_exception_handler();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    public function testSendRemindersForContactTask()
    {
        Notification::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $task = Task::factory()->create([
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
        ]);

        $this->reminderService->sendReminders();

        Notification::assertSentTo($contact, TaskReminderNotification::class);
        Notification::assertSentTo($user, TaskReminderNotification::class);
        $this->assertTrue($task->fresh()->reminder_sent);
    }

    public function testSendRemindersForLeadTask()
    {
        Notification::fake();

        $user = User::factory()->create();
        $lead = Lead::factory()->create();
        $task = Task::factory()->create([
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
        ]);

        $this->reminderService->sendReminders();

        Notification::assertSentTo($lead, TaskReminderNotification::class);
        Notification::assertSentTo($user, TaskReminderNotification::class);
        $this->assertTrue($task->fresh()->reminder_sent);
    }

    public function testHandleFailedNotification()
    {
        Notification::fake();
        Log::shouldReceive('error')->once();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $task = Task::factory()->create([
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
        ]);

        Notification::shouldReceive('send')->andThrow(new \Exception('Notification failed'));

        $this->reminderService->sendReminders();

        $this->assertFalse($task->fresh()->reminder_sent);
    }

    public function testLogReminderActivity()
    {
        Log::shouldReceive('info')->once()->withArgs(function ($message) {
            return strpos($message, 'Reminder sent successfully') !== false;
        });

        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $task = Task::factory()->create([
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
        ]);

        $this->reminderService->sendReminders();

        $this->assertTrue($task->fresh()->reminder_sent);
    }

    public function testScheduleReminder()
    {
        $task = Task::factory()->create();
        $reminderDate = now()->addDays(2);

        $this->reminderService->scheduleReminder($task, $reminderDate);

        $this->assertEquals($reminderDate, $task->fresh()->reminder_date);
        $this->assertFalse($task->fresh()->reminder_sent);
    }
}