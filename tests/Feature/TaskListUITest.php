<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class TaskListUITest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_task_list_displays_tasks()
    {
        $tasks = Task::factory()->count(5)->create();

        Livewire::test('task-list')
            ->assertSee($tasks[0]->name)
            ->assertSee($tasks[4]->name);
    }

    public function test_task_search_functionality()
    {
        $task1 = Task::factory()->create(['name' => 'Test Task 1']);
        $task2 = Task::factory()->create(['name' => 'Another Task']);

        Livewire::test('task-list')
            ->set('search', 'Test')
            ->assertSee('Test Task 1')
            ->assertDontSee('Another Task');
    }

    public function test_task_status_filtering()
    {
        $task1 = Task::factory()->create(['status' => 'pending']);
        $task2 = Task::factory()->create(['status' => 'completed']);

        Livewire::test('task-list')
            ->set('status', 'pending')
            ->assertSee($task1->name)
            ->assertDontSee($task2->name);
    }

    public function test_task_lead_filtering()
    {
        $lead1 = Lead::factory()->create();
        $lead2 = Lead::factory()->create();
        $task1 = Task::factory()->create(['lead_id' => $lead1->id]);
        $task2 = Task::factory()->create(['lead_id' => $lead2->id]);

        Livewire::test('task-list')
            ->set('leadFilter', $lead1->id)
            ->assertSee($task1->name)
            ->assertDontSee($task2->name);
    }

    public function test_task_sorting()
    {
        $task1 = Task::factory()->create(['name' => 'A Task', 'due_date' => now()->addDays(5)]);
        $task2 = Task::factory()->create(['name' => 'B Task', 'due_date' => now()->addDays(2)]);

        Livewire::test('task-list')
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['A Task', 'B Task'])
            ->call('sortBy', 'due_date')
            ->assertSeeInOrder(['B Task', 'A Task']);
    }

    public function test_task_list_displays_lead_information()
    {
        $lead = Lead::factory()->create(['name' => 'Test Lead']);
        $task = Task::factory()->create(['lead_id' => $lead->id]);

        Livewire::test('task-list')
            ->assertSee($task->name)
            ->assertSee('Test Lead');
    }
}