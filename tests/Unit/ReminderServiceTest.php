<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $reminderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reminderService = new ReminderService();
    }

    public function testSendReminders()
    {
        Notification::fake();

        $user = User::factory()->create();
        $task = Task::factory()->create([
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
            'contact_id' => $user->id,
        ]);

        $this->reminderService->sendReminders();

        Notification::assertSentTo($user, TaskReminderNotification::class);
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