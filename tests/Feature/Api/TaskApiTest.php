<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_can_list_tasks()
    {
        Task::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_create_task()
    {
        $taskData = [
            'title' => 'New Task',
            'description' => 'Task description',
            'due_date' => '2023-06-30',
            'status' => 'pending',
        ];

        $response = $this->postJson('/api/v1/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonFragment($taskData);
    }

    public function test_can_show_task()
    {
        $task = Task::factory()->create();

        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson($task->toArray());
    }

    public function test_can_update_task()
    {
        $task = Task::factory()->create();
        $updatedData = [
            'title' => 'Updated Task',
            'description' => 'Updated description',
            'due_date' => '2023-07-15',
            'status' => 'in_progress',
        ];

        $response = $this->putJson("/api/v1/tasks/{$task->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJsonFragment($updatedData);
    }

    public function test_can_delete_task()
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}