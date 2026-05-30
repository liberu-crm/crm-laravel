<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ContactListController extends Controller
{
    /**
     * Handle the index request for the contacts list.
     */
    public function index(Request $request, $created_at = null)
    {
        $query = Contact::query();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('last_name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('phone_number', 'like', '%'.$term.'%')
                    ->orWhere('company_size', 'like', '%'.$term.'%')
                    ->orWhere('industry', 'like', '%'.$term.'%');
            });
        }

        if ($created_at) {
            try {
                $query->where('created_at', '>=', Carbon::parse($created_at));
            } catch (\Exception $e) {
                // Invalid date string; skip filter
            }
        }

        $contacts = $query->get();

        return view('contacts.list', compact('contacts'));
    }

    /**
     * Bulk delete contacts by IDs.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:contacts,id',
        ]);

        $query = Contact::whereIn('id', $validated['ids']);
        $query->byTeam($request->user()?->currentTeam?->id);
        $count = $query->delete();

        return response()->json(['deleted' => $count]);
    }

    /**
     * Autocomplete contacts by name or email.
     */
    public function autocomplete(Request $request)
    {
        $term = $request->input('query', '');

        $contacts = Contact::where(function ($q) use ($term) {
            $q->where('name', 'like', $term.'%')
                ->orWhere('email', 'like', '%'.$term.'%');
        })
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($contacts);
    }
}
