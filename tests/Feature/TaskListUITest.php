<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\Lead;
use App\Http\Livewire\TaskList;
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
        $tasks = Task::factory()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(TaskList::class)
            ->set('search', '')
            ->assertSee($tasks[0]->name)
            ->assertSee($tasks[2]->name);
    }

    public function test_task_search_functionality()
    {
        $task1 = Task::factory()->create(['name' => 'Test Task Alpha']);
        $task2 = Task::factory()->create(['name' => 'Another Unrelated Task']);

        Livewire::actingAs($this->user)
            ->test(TaskList::class)
            ->set('search', 'Test Task Alpha')
            ->assertSee('Test Task Alpha')
            ->assertDontSee('Another Unrelated Task');
    }

    public function test_task_status_filtering()
    {
        $task1 = Task::factory()->create(['name' => 'Pending Task Alpha', 'status' => 'pending']);
        $task2 = Task::factory()->create(['name' => 'Completed Task Beta', 'status' => 'completed']);

        Livewire::actingAs($this->user)
            ->test(TaskList::class)
            ->set('status', 'pending')
            ->assertSee('Pending Task Alpha')
            ->assertDontSee('Completed Task Beta');
    }

    public function test_task_lead_filtering()
    {
        $lead1 = Lead::factory()->create();
        $lead2 = Lead::factory()->create();
        $task1 = Task::factory()->create(['name' => 'Task for Lead One', 'lead_id' => $lead1->id]);
        $task2 = Task::factory()->create(['name' => 'Task for Lead Two', 'lead_id' => $lead2->id]);

        Livewire::actingAs($this->user)
            ->test(TaskList::class)
            ->set('leadFilter', $lead1->id)
            ->assertSee('Task for Lead One')
            ->assertDontSee('Task for Lead Two');
    }

    public function test_task_sorting()
    {
        $task1 = Task::factory()->create(['name' => 'Aardvark Task', 'due_date' => now()->addDays(5)]);
        $task2 = Task::factory()->create(['name' => 'Zebra Task', 'due_date' => now()->addDays(2)]);

        Livewire::actingAs($this->user)
            ->test(TaskList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Aardvark Task', 'Zebra Task']);
    }
}
