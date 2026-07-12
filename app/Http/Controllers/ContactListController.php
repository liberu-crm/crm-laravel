<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactListController extends Controller
{
    public function index(Request $request, ?string $created_at = null): View
    {
        $query = Contact::query();

        if ($request->filled('search')) {
            $term = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('last_name', 'like', '%'.$term.'%')
                // email is encrypted — exact match via the blind index only.
                ->orWhere('email_hash', Contact::hashEmail($term))
                ->orWhere('phone_number', 'like', '%'.$term.'%')
                ->orWhere('company_size', 'like', '%'.$term.'%')
                ->orWhere('industry', 'like', '%'.$term.'%')
            );
        }

        if ($created_at) {
            try {
                $query->where('created_at', '>=', Carbon::parse($created_at));
            } catch (\Exception) {
                // Invalid date string — skip filter
            }
        }

        $contacts = $query->get();

        return view('contacts.list', ['contacts' => $contacts]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:contacts,id',
        ]);

        $count = Contact::whereIn('id', $validated['ids'])
            ->byTeam($request->user()?->currentTeam?->id)
            ->delete();

        return response()->json(['deleted' => $count]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $term = $request->string('query')->toString();

        $contacts = Contact::where(fn ($q) => $q
            ->where('name', 'like', $term.'%')
            // email is encrypted — exact match via the blind index only.
            ->orWhere('email_hash', Contact::hashEmail($term))
        )
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($contacts);
    }
}
