<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $reminderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reminderService = new ReminderService;
        restore_error_handler();
        restore_exception_handler();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    public function test_send_reminders_for_contact_task(): void
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

    public function test_send_reminders_for_lead_task(): void
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

    public function test_handle_failed_notification(): void
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

    public function test_log_reminder_activity(): void
    {
        Notification::fake();
        Log::shouldReceive('info')->once()->withArgs(fn($message) => str_contains((string) $message, 'Reminder sent successfully'));

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

    public function test_schedule_reminder(): void
    {
        $task = Task::factory()->create();
        $reminderDate = now()->addDays(2)->startOfSecond();

        $this->reminderService->scheduleReminder($task, $reminderDate);

        $this->assertEquals(
            $reminderDate->toDateTimeString(),
            $task->fresh()->reminder_date->toDateTimeString()
        );
        $this->assertFalse($task->fresh()->reminder_sent);
    }
}
