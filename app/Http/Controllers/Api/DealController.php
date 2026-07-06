<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(Request $request)
    {
        return Deal::byTeam($request->user()?->currentTeam?->id)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|numeric',
            'stage' => 'nullable|string|max:255',
            'close_date' => 'nullable|date',
            'probability' => 'nullable|integer|min:0|max:100',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'pipeline_id' => 'nullable|integer|exists:pipelines,id',
            'stage_id' => 'nullable|integer|exists:stages,id',
        ]);

        $validated['team_id'] = $request->user()?->currentTeam?->id;
        $deal = Deal::create($validated);

        return response()->json($deal, 201);
    }

    public function show(Request $request, Deal $deal): Deal
    {
        abort_unless($deal->belongsToTeam($request->user()?->currentTeam?->id), 403);

        return $deal;
    }

    public function update(Request $request, Deal $deal)
    {
        abort_unless($deal->belongsToTeam($request->user()?->currentTeam?->id), 403);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'value' => 'numeric',
            'stage' => 'string|max:255',
            'close_date' => 'nullable|date',
            'probability' => 'nullable|integer|min:0|max:100',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'pipeline_id' => 'nullable|integer|exists:pipelines,id',
            'stage_id' => 'nullable|integer|exists:stages,id',
        ]);

        $deal->update($validated);

        return response()->json($deal, 200);
    }

    public function destroy(Request $request, Deal $deal)
    {
        abort_unless($deal->belongsToTeam($request->user()?->currentTeam?->id), 403);

        $deal->delete();

        return response()->json(null, 204);
    }

    /**
     * Bulk update deals.
     *
     * Expects: { "ids": [1,2,3], "data": { "status": "won", ... } }
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:deals,id',
            'data' => 'required|array',
            'data.stage' => 'sometimes|string|max:255',
            'data.stage_id' => 'sometimes|integer|exists:stages,id',
        ]);

        // 'stage' is the real string column on deals; there is no 'status'
        // column (that lives on contacts), so updating it 500s.
        $allowedFields = ['stage', 'stage_id', 'pipeline_id'];
        $updateData = array_intersect_key($request->input('data'), array_flip($allowedFields));

        if ($updateData === []) {
            return response()->json(['message' => 'No valid fields to update.'], 422);
        }

        $query = Deal::whereIn('id', $request->input('ids'));
        $query->byTeam($request->user()?->currentTeam?->id);
        $count = $query->update($updateData);

        return response()->json(['updated' => $count]);
    }

    /**
     * Bulk delete deals.
     *
     * Expects: { "ids": [1,2,3] }
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:deals,id',
        ]);

        $query = Deal::whereIn('id', $request->input('ids'));
        $query->byTeam($request->user()?->currentTeam?->id);
        $count = $query->delete();

        return response()->json(['deleted' => $count]);
    }

    /**
     * Bulk assign deals to a user.
     *
     * Expects: { "ids": [1,2,3], "user_id": 5 }
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:deals,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        // Assignee must be a member of the caller's current team, else this
        // leaks records across tenants. Refuse before touching any record.
        $team = $request->user()?->currentTeam;
        $assignee = \App\Models\User::find($request->input('user_id'));
        abort_unless($team && $assignee?->belongsToTeam($team), 403);

        $query = Deal::whereIn('id', $request->input('ids'));
        $query->byTeam($request->user()?->currentTeam?->id);
        $count = $query->update(['user_id' => $request->input('user_id')]);

        return response()->json(['assigned' => $count]);
    }
}
