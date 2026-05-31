<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        return Contact::byTeam($request->user()?->currentTeam?->id)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:contacts,email',
            'phone_number' => 'nullable|string|max:20',
            'last_name' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'lifecycle_stage' => 'nullable|string|max:255',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $validated['team_id'] = $request->user()?->currentTeam?->id;
        $contact = Contact::create($validated);

        return response()->json($contact, 201);
    }

    public function show(Request $request, Contact $contact): Contact
    {
        abort_unless($contact->belongsToTeam($request->user()?->currentTeam?->id), 403);

        return $contact;
    }

    public function update(Request $request, Contact $contact)
    {
        abort_unless($contact->belongsToTeam($request->user()?->currentTeam?->id), 403);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:contacts,email,'.$contact->id,
            'phone_number' => 'nullable|string|max:20',
            'last_name' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'lifecycle_stage' => 'nullable|string|max:255',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $contact->update($validated);

        return response()->json($contact, 200);
    }

    public function destroy(Request $request, Contact $contact)
    {
        abort_unless($contact->belongsToTeam($request->user()?->currentTeam?->id), 403);

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
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:contacts,id',
            'data' => 'required|array',
            'data.status' => 'sometimes|string|max:255',
            'data.lifecycle_stage' => 'sometimes|string|max:255',
            'data.source' => 'sometimes|string|max:255',
        ]);

        $allowedFields = ['status', 'lifecycle_stage', 'source', 'industry'];
        $updateData = array_intersect_key($request->input('data'), array_flip($allowedFields));

        if ($updateData === []) {
            return response()->json(['message' => 'No valid fields to update.'], 422);
        }

        $query = Contact::whereIn('id', $request->input('ids'));
        $query->byTeam($request->user()?->currentTeam?->id);
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
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:contacts,id',
        ]);

        $query = Contact::whereIn('id', $request->input('ids'));
        $query->byTeam($request->user()?->currentTeam?->id);
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
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:contacts,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $query = Contact::whereIn('id', $request->input('ids'));
        $query->byTeam($request->user()?->currentTeam?->id);
        $count = $query->update(['user_id' => $request->input('user_id')]);

        return response()->json(['assigned' => $count]);
    }
}
