<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return Contact::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:contacts,email',
            'phone' => 'nullable|string|max:20',
        ]);

        $contact = Contact::create($request->all());
        return response()->json($contact, 201);
    }

    public function show(Contact $contact)
    {
        return $contact;
    }

    public function update(Request $request, Contact $contact)
    {
        $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:contacts,email,' . $contact->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $contact->update($request->all());
        return response()->json($contact, 200);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return response()->json(null, 204);
    }

    /**
     * Bulk update contacts.
     *
     * Expects: { "ids": [1,2,3], "data": { "status": "active", ... } }
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids'        => 'required|array|min:1',
            'ids.*'      => 'integer|exists:contacts,id',
            'data'       => 'required|array',
            'data.status'          => 'sometimes|string|max:255',
            'data.lifecycle_stage' => 'sometimes|string|max:255',
            'data.source'          => 'sometimes|string|max:255',
        ]);

        $allowedFields = ['status', 'lifecycle_stage', 'source', 'industry'];
        $updateData = array_intersect_key($request->input('data'), array_flip($allowedFields));

        if (empty($updateData)) {
            return response()->json(['message' => 'No valid fields to update.'], 422);
        }

        $query = Contact::whereIn('id', $request->input('ids'));
        $this->applyTeamScope($request, $query);
        $count = $query->update($updateData);

        return response()->json(['updated' => $count]);
    }

    /**
     * Bulk delete contacts.
     *
     * Expects: { "ids": [1,2,3] }
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:contacts,id',
        ]);

        $query = Contact::whereIn('id', $request->input('ids'));
        $this->applyTeamScope($request, $query);
        $count = $query->delete();

        return response()->json(['deleted' => $count]);
    }

    /**
     * Bulk assign contacts to a user.
     *
     * Expects: { "ids": [1,2,3], "user_id": 5 }
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'ids'     => 'required|array|min:1',
            'ids.*'   => 'integer|exists:contacts,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $query = Contact::whereIn('id', $request->input('ids'));
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