<?php

namespace Tests\Feature\Livewire;

use App\Livewire\TaskList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskListTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_list_component_renders(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(TaskList::class)
            ->assertStatus(200);
    }

    public function test_task_list_can_be_searched(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        Task::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Call client']);
        Task::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Send proposal']);

        Livewire::actingAs($user)
            ->test(TaskList::class)
            ->set('search', 'Call')
            ->assertSee('Call client')
            ->assertDontSee('Send proposal');
    }

    public function test_task_list_can_be_filtered_by_status(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        Task::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Done task', 'status' => 'completed']);
        Task::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Open task', 'status' => 'pending']);

        Livewire::actingAs($user)
            ->test(TaskList::class)
            ->set('status', 'completed')
            ->assertSee('Done task')
            ->assertDontSee('Open task');
    }

    public function test_delete_task_removes_from_database(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $task = Task::factory()->create(['team_id' => $user->currentTeam->id]);

        Livewire::actingAs($user)
            ->test(TaskList::class)
            ->call('deleteTask', $task->id);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_sort_by_changes_sort_direction(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $component = Livewire::actingAs($user)
            ->test(TaskList::class)
            ->set('sortField', 'due_date')
            ->set('sortDirection', 'asc')
            ->call('sortBy', 'due_date');

        $component->assertSet('sortDirection', 'desc');
    }
}
