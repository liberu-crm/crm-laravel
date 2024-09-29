<?php

namespace App\Services;

use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use Carbon\Carbon;

class ReminderService
{
    public function sendReminders()
    {
        $tasks = Task::where('reminder_date', '<=', Carbon::now())
            ->where('reminder_sent', false)
            ->get();

        foreach ($tasks as $task) {
            $task->contact->notify(new TaskReminderNotification($task));
            $task->update(['reminder_sent' => true]);
        }
    }

    public function scheduleReminder(Task $task, Carbon $reminderDate)
    {
        $task->update([
            'reminder_date' => $reminderDate,
            'reminder_sent' => false,
        ]);
    }
}