<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Events\TaskOverdue;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotifyOverdueTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_task_overdue_once_for_past_due_incomplete_tasks(): void
    {
        Event::fake([TaskOverdue::class]);
        $team = Team::factory()->create();

        $overdue = Task::factory()->create([
            'team_id' => $team->id, 'due_date' => now()->subDay(), 'status' => 'pending',
        ]);
        Task::factory()->create([ // not yet due
            'team_id' => $team->id, 'due_date' => now()->addDay(), 'status' => 'pending',
        ]);
        Task::factory()->create([ // past due but completed
            'team_id' => $team->id, 'due_date' => now()->subDay(), 'status' => 'completed',
        ]);

        $this->artisan('tasks:notify-overdue')->assertSuccessful();

        Event::assertDispatchedTimes(TaskOverdue::class, 1);
        Event::assertDispatched(TaskOverdue::class, fn (TaskOverdue $e): bool => $e->task->id === $overdue->id);
        $this->assertTrue($overdue->fresh()->overdue_notified);
    }

    public function test_does_not_re_notify_an_already_notified_task(): void
    {
        Event::fake([TaskOverdue::class]);
        $team = Team::factory()->create();
        Task::factory()->create([
            'team_id' => $team->id, 'due_date' => now()->subDay(),
            'status' => 'pending', 'overdue_notified' => true,
        ]);

        $this->artisan('tasks:notify-overdue')->assertSuccessful();

        Event::assertNotDispatched(TaskOverdue::class);
    }
}
