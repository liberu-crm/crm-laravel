<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use App\Services\ReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    protected $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    public function index()
    {
        $tasks = Task::where('assigned_to', Auth::id())->orderBy('due_date')->get();
        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        $contacts = Contact::all();
        $leads = Lead::all();
        $users = User::all();
        return view('tasks.create', compact('contacts', 'leads', 'users'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'contact_id' => 'nullable|exists:contacts,id',
            'lead_id' => 'nullable|exists:leads,id',
            'assigned_to' => 'required|exists:users,id',
            'reminder_date' => 'nullable|date|before_or_equal:due_date',
        ]);

        $task = Task::create($validatedData);

        if ($request->has('reminder_date')) {
            $this->reminderService->scheduleReminder($task, $request->reminder_date);
        }

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function edit(Task $task)
    {
        $contacts = Contact::all();
        $leads = Lead::all();
        $users = User::all();
        return view('tasks.edit', compact('task', 'contacts', 'leads', 'users'));
    }

    public function update(Request $request, Task $task)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'contact_id' => 'nullable|exists:contacts,id',
            'lead_id' => 'nullable|exists:leads,id',
            'assigned_to' => 'required|exists:users,id',
            'reminder_date' => 'nullable|date|before_or_equal:due_date',
        ]);

        $task->update($validatedData);

        if ($request->has('reminder_date')) {
            $this->reminderService->scheduleReminder($task, $request->reminder_date);
        }

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function complete(Task $task)
    {
        $task->markAsComplete();
        return redirect()->back()->with('success', 'Task marked as complete.');
    }

    public function incomplete(Task $task)
    {
        $task->markAsIncomplete();
        return redirect()->back()->with('success', 'Task marked as incomplete.');
    }

    public function assign(Request $request, Task $task)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validatedData['user_id']);
        $task->assign($user);

        return redirect()->back()->with('success', 'Task assigned successfully.');
    }
}