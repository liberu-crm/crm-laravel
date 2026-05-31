<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_can_be_created(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $task = Task::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_task_belongs_to_contact(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $contact = Contact::factory()->create(['team_id' => $user->currentTeam->id]);
        $task = Task::factory()->create([
            'team_id' => $user->currentTeam->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertInstanceOf(Contact::class, $task->contact);
    }

    public function test_task_due_date_is_cast_to_datetime(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $task = Task::factory()->create([
            'team_id' => $user->currentTeam->id,
            'due_date' => '2025-06-01 10:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $task->due_date);
    }

    public function test_task_reminder_sent_is_cast_to_boolean(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $task = Task::factory()->create([
            'team_id' => $user->currentTeam->id,
            'reminder_sent' => false,
        ]);

        $this->assertIsBool($task->reminder_sent);
    }

    public function test_task_fillable_attributes(): void
    {
        $task = new Task;
        $this->assertContains('name', $task->getFillable());
        $this->assertContains('status', $task->getFillable());
        $this->assertContains('due_date', $task->getFillable());
    }
}
