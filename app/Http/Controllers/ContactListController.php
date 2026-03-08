<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactListController extends Controller
{
    /**
     * Handle the index request for the contacts list.
     */
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                  ->orWhere('last_name', 'like', '%' . $term . '%')
                  ->orWhere('email', 'like', '%' . $term . '%')
                  ->orWhere('phone_number', 'like', '%' . $term . '%')
                  ->orWhere('company_size', 'like', '%' . $term . '%')
                  ->orWhere('industry', 'like', '%' . $term . '%');
            });
        }

        if ($request->filled('created_at')) {
            $query->where('created_at', '>=', $request->created_at);
        }

        $contacts = $query->get();

        return view('contacts.list', compact('contacts'));
    }

    /**
     * Bulk delete contacts by IDs.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!empty($ids)) {
            Contact::whereIn('id', $ids)->delete();
        }

        return response()->json(['deleted' => count($ids)]);
    }

    /**
     * Autocomplete contacts by name or email.
     */
    public function autocomplete(Request $request)
    {
        $term = $request->input('query', '');

        $contacts = Contact::where(function ($q) use ($term) {
            $q->where('name', 'like', $term . '%')
              ->orWhere('email', 'like', '%' . $term . '%');
        })
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($contacts);
    }
}
