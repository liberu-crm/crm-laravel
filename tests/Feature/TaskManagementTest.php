<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateTaskForContact()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/tasks', [
            'name' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
        ]);
    }

    public function testCreateTaskForLead()
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/tasks', [
            'name' => 'Test Task',
            'description' => 'Test Description',
            'due_date' => now()->addDays(2),
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
        ]);
    }

    public function testAssignTask()
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);
        $this->actingAs($user);

        $response = $this->patch("/tasks/{$task->id}/assign", [
            'user_id' => $assignee->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function testMarkTaskAsComplete()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id, 'status' => 'incomplete']);
        $this->actingAs($user);

        $response = $this->patch("/tasks/{$task->id}/complete");

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function testMarkTaskAsIncomplete()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id, 'status' => 'completed']);
        $this->actingAs($user);

        $response = $this->patch("/tasks/{$task->id}/incomplete");

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'incomplete',
        ]);
    }

    public function testListTasks()
    {

        $user = User::factory()->create();
        $tasks = Task::factory()->count(5)->create(['assigned_to' => $user->id]);
        $this->actingAs($user);

        $response = $this->get('/tasks');

        $response->assertStatus(200);
        $response->assertViewHas('tasks');
        $viewTasks = $response->viewData('tasks');
        $this->assertCount(5, $viewTasks);
    }

    public function testFilterTasksByStatus()
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['assigned_to' => $user->id, 'status' => 'completed']);
        Task::factory()->count(2)->create(['assigned_to' => $user->id, 'status' => 'incomplete']);
        $this->actingAs($user);

        $response = $this->get('/tasks?status=completed');

        $response->assertStatus(200);
        $response->assertViewHas('tasks');
        $viewTasks = $response->viewData('tasks');
        $this->assertCount(3, $viewTasks);
        $this->assertTrue($viewTasks->every(fn($task) => $task->status === 'completed'));
    }

    public function testSearchTasks()
    {
        $user = User::factory()->create();
        Task::factory()->create(['assigned_to' => $user->id, 'name' => 'Test Task 1']);
        Task::factory()->create(['assigned_to' => $user->id, 'name' => 'Test Task 2']);
        Task::factory()->create(['assigned_to' => $user->id, 'name' => 'Another Task']);
        $this->actingAs($user);

        $response = $this->get('/tasks?search=Test');

        $response->assertStatus(200);
        $response->assertViewHas('tasks');
        $viewTasks = $response->viewData('tasks');
        $this->assertCount(2, $viewTasks);
        $this->assertTrue($viewTasks->every(fn($task) => str_contains($task->name, 'Test')));
    }
}