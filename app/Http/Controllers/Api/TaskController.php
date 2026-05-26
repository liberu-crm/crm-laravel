<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        return Task::byTeam($request->user()?->currentTeam?->id)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'due_date'      => 'nullable|date',
            'status'        => 'nullable|string|in:pending,in_progress,completed',
            'assigned_to'   => 'nullable|integer|exists:users,id',
            'contact_id'    => 'nullable|integer|exists:contacts,id',
            'lead_id'       => 'nullable|integer|exists:leads,id',
            'company_id'    => 'nullable|integer|exists:companies,id',
            'calendar_type' => 'nullable|string|in:google,outlook',
        ]);

        $validated['team_id'] = $request->user()?->currentTeam?->id;
        $task = Task::create($validated);

        return response()->json($task, 201);
    }

    public function show(Request $request, Task $task)
    {
        abort_unless($task->belongsToTeam($request->user()?->currentTeam?->id), 403);

        return $task;
    }

    public function update(Request $request, Task $task)
    {
        abort_unless($task->belongsToTeam($request->user()?->currentTeam?->id), 403);

        $validated = $request->validate([
            'name'          => 'string|max:255',
            'description'   => 'nullable|string',
            'due_date'      => 'nullable|date',
            'status'        => 'string|in:pending,in_progress,completed',
            'assigned_to'   => 'nullable|integer|exists:users,id',
            'contact_id'    => 'nullable|integer|exists:contacts,id',
            'lead_id'       => 'nullable|integer|exists:leads,id',
            'company_id'    => 'nullable|integer|exists:companies,id',
            'calendar_type' => 'nullable|string|in:google,outlook',
        ]);

        $task->update($validated);

        return response()->json($task, 200);
    }

    public function destroy(Request $request, Task $task)
    {
        abort_unless($task->belongsToTeam($request->user()?->currentTeam?->id), 403);

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
        $query->byTeam($request->user()?->currentTeam?->id);
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
        $query->byTeam($request->user()?->currentTeam?->id);
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
        $query->byTeam($request->user()?->currentTeam?->id);
        $count = $query->update(['user_id' => $request->input('user_id')]);

        return response()->json(['assigned' => $count]);
    }
}