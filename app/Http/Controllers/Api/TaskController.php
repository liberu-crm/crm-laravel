<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return Task::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string|in:pending,in_progress,completed',
        ]);

        $task = Task::create($request->only(['name', 'description', 'due_date', 'status', 'assigned_to', 'contact_id', 'lead_id', 'company_id', 'calendar_type']));
        return response()->json($task, 201);
    }

    public function show(Task $task)
    {
        return $task;
    }

    public function update(Request $request, Task $task)
    {
        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'string|in:pending,in_progress,completed',
        ]);

        $task->update($request->only(['name', 'description', 'due_date', 'status', 'assigned_to', 'contact_id', 'lead_id', 'company_id', 'calendar_type']));
        return response()->json($task, 200);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(null, 204);
    }

    /**
     * Bulk update tasks.
     *
     * Expects: { "ids": [1,2,3], "data": { "status": "completed" } }
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids'          => 'required|array|min:1',
            'ids.*'        => 'integer|exists:tasks,id',
            'data'         => 'required|array',
            'data.status'  => 'sometimes|string|in:pending,in_progress,completed',
            'data.due_date' => 'sometimes|nullable|date',
        ]);

        $allowedFields = ['status', 'due_date'];
        $updateData = array_intersect_key($request->input('data'), array_flip($allowedFields));

        if (empty($updateData)) {
            return response()->json(['message' => 'No valid fields to update.'], 422);
        }

        $query = Task::whereIn('id', $request->input('ids'));
        $this->applyTeamScope($request, $query);
        $count = $query->update($updateData);

        return response()->json(['updated' => $count]);
    }

    /**
     * Bulk delete tasks.
     *
     * Expects: { "ids": [1,2,3] }
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:tasks,id',
        ]);

        $query = Task::whereIn('id', $request->input('ids'));
        $this->applyTeamScope($request, $query);
        $count = $query->delete();

        return response()->json(['deleted' => $count]);
    }

    /**
     * Bulk assign tasks to a user.
     *
     * Expects: { "ids": [1,2,3], "user_id": 5 }
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'ids'     => 'required|array|min:1',
            'ids.*'   => 'integer|exists:tasks,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $query = Task::whereIn('id', $request->input('ids'));
        $this->applyTeamScope($request, $query);
        $count = $query->update(['user_id' => $request->input('user_id')]);

        return response()->json(['assigned' => $count]);
    }

    /**
     * Scope the query to the authenticated user's current team when available.
     */
    private function applyTeamScope(Request $request, $query): void
    {
        $teamId = $request->user()?->currentTeam?->id;
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
    }
}