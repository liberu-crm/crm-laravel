<?php

namespace App\Events;

use App\Models\Task;
use App\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;

// ponytail: FLAG — no dispatch wired. "Overdue" is a state-over-time condition
// (Task::isOverdue(): due_date past & not completed), not a model lifecycle event,
// so it needs a scheduled scan. The existing ReminderService keys off reminder_date
// (a different concept) and is out of the allowed edit set. Add a scheduled command
// that dispatches TaskOverdue for Task::isOverdue() rows when the product wants it.
class TaskOverdue
{
    use Dispatchable;

    public function __construct(public Task $task)
    {
    }

    /** Team this event belongs to — used to scope notifications (anti cross-tenant leak). */
    public function team(): ?Team
    {
        return $this->task->team;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->task->id,
            'name' => $this->task->name,
            'due_date' => $this->task->due_date?->toDateTimeString(),
            'status' => $this->task->status,
            'assigned_to' => $this->task->assigned_to,
            'team_id' => $this->task->team_id,
        ];
    }
}
