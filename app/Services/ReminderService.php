<?php

namespace App\Services;

use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReminderService
{
    public function sendReminders()
    {
        $tasks = Task::where('reminder_date', '<=', Carbon::now())
            ->where('reminder_sent', false)
            ->get();

        foreach ($tasks as $task) {
            try {
                $task->contact->notify(new TaskReminderNotification($task));
                $task->update(['reminder_sent' => true]);
                Log::info("Reminder sent successfully for task ID: {$task->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send reminder for task ID: {$task->id}. Error: {$e->getMessage()}");
            }
        }
    }

    public function scheduleReminder(Task $task, Carbon $reminderDate)
    {
        try {
            $task->update([
                'reminder_date' => $reminderDate,
                'reminder_sent' => false,
            ]);
            Log::info("Reminder scheduled successfully for task ID: {$task->id}");
        } catch (\Exception $e) {
            Log::error("Failed to schedule reminder for task ID: {$task->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }
}