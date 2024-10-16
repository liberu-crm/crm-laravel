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
}