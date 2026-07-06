<?php

namespace App\Console\Commands;

use App\Events\TaskOverdue;
use App\Models\Task;
use Illuminate\Console\Command;

class NotifyOverdueTasks extends Command
{
    protected $signature = 'tasks:notify-overdue';

    protected $description = 'Dispatch TaskOverdue for past-due, incomplete tasks (once per task).';

    public function handle(): int
    {
        $count = 0;

        // Runs in console: IsTenantModel / RestrictsToOwner scopes are inert
        // (no tenant/auth context), so this scans every team's tasks. Each
        // TaskOverdue event is team-scoped by the notification listener.
        Task::query()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->where('overdue_notified', false)
            ->chunkById(200, function ($tasks) use (&$count): void {
                foreach ($tasks as $task) {
                    TaskOverdue::dispatch($task);
                    $task->update(['overdue_notified' => true]);
                    $count++;
                }
            });

        $this->info("Dispatched TaskOverdue for {$count} task(s).");

        return self::SUCCESS;
    }
}
