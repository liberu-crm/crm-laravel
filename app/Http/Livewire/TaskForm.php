<?php

namespace App\Http\Livewire;

use App\Models\Task;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Livewire\Component;

class TaskForm extends Component
{
    public $task;
    public $taskId;
    public $name;
    public $description;
    public $due_date;
    public $contact_id;
    public $lead_id;
    public $assigned_to;
    public $reminder_date;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'due_date' => 'required|date',
        'contact_id' => 'nullable|exists:contacts,id',
        'lead_id' => 'nullable|exists:leads,id',
        'assigned_to' => 'required|exists:users,id',
        'reminder_date' => 'nullable|date|before_or_equal:due_date',
    ];

    public function mount($taskId = null)
    {
        if ($taskId) {
            $this->task = Task::findOrFail($taskId);
            $this->taskId = $this->task->id;
            $this->name = $this->task->name;
            $this->description = $this->task->description;
            $this->due_date = $this->task->due_date->format('Y-m-d\TH:i');
            $this->contact_id = $this->task->contact_id;
            $this->lead_id = $this->task->lead_id;
            $this->assigned_to = $this->task->assigned_to;
            $this->reminder_date = $this->task->reminder_date ? $this->task->reminder_date->format('Y-m-d\TH:i') : null;
        }
    }

    public function save()
    {
        $this->validate();

        $taskData = [
            'name' => $this->name,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'contact_id' => $this->contact_id,
            'lead_id' => $this->lead_id,
            'assigned_to' => $this->assigned_to,
            'reminder_date' => $this->reminder_date,
        ];

        if ($this->taskId) {
            $this->task->update($taskData);
            session()->flash('message', 'Task updated successfully.');
        } else {
            Task::create($taskData);
            session()->flash('message', 'Task created successfully.');
        }

        return redirect()->route('tasks.index');
    }

    public function render()
    {
        return view('livewire.task-form', [
            'contacts' => Contact::all(),
            'leads' => Lead::all(),
            'users' => User::all(),
        ]);
    }
}