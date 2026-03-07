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

        $task = Task::factory()->create([
            'name' => 'Test Task',
            'description' => 'Test Description',
            'contact_id' => $contact->id,
            'assigned_to' => $user->id,
        ]);

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

        $task = Task::factory()->create([
            'name' => 'Lead Task',
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'name' => 'Lead Task',
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
        ]);
    }

    public function testAssignTask()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $task = Task::factory()->create(['assigned_to' => $user1->id]);

        $task->update(['assigned_to' => $user2->id]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $user2->id,
        ]);
    }

    public function testUpdateTaskStatus()
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $task->update(['status' => 'completed']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function testTaskFilterByStatus()
    {
        Task::factory()->count(3)->create(['status' => 'pending']);
        Task::factory()->count(2)->create(['status' => 'completed']);

        $pendingTasks = Task::where('status', 'pending')->count();
        $completedTasks = Task::where('status', 'completed')->count();

        $this->assertEquals(3, $pendingTasks);
        $this->assertEquals(2, $completedTasks);
    }

    public function testTaskSearchByName()
    {
        Task::factory()->create(['name' => 'Unique Task Alpha Search']);
        Task::factory()->create(['name' => 'Another Unrelated Task']);

        $results = Task::where('name', 'like', '%Unique Task Alpha%')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Unique Task Alpha Search', $results->first()->name);
    }
}
