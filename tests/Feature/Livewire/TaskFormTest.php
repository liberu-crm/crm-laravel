<?php

namespace Tests\Feature\Livewire;

use App\Livewire\TaskForm;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_form_mounts(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(TaskForm::class)
            ->assertStatus(200);
    }

    public function test_save_persists_a_new_task(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(TaskForm::class)
            ->set('name', 'Follow up call')
            ->set('description', 'Ring the client back')
            ->set('due_date', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('assigned_to', $user->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'name' => 'Follow up call',
            'assigned_to' => $user->id,
        ]);
    }

    public function test_save_requires_name_due_date_and_assignee(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(TaskForm::class)
            ->set('name', '')
            ->set('due_date', '')
            ->call('save')
            ->assertHasErrors([
                'name' => 'required',
                'due_date' => 'required',
                'assigned_to' => 'required',
            ]);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_mount_loads_existing_task_and_save_updates_it(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $task = Task::factory()->create([
            'team_id' => $user->currentTeam->id,
            'assigned_to' => $user->id,
            'name' => 'Original name',
        ]);

        Livewire::actingAs($user)
            ->test(TaskForm::class, ['taskId' => $task->id])
            ->assertSet('name', 'Original name')
            ->set('name', 'Updated name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Updated name',
        ]);
    }
}
