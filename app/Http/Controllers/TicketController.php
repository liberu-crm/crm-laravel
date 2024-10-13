<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'subject' => 'required|max:255',
            'body' => 'required',
        ]);

        $ticket = Ticket::create([
            'subject' => $validatedData['subject'],
            'body' => $validatedData['body'],
            'status' => 'open',
            'priority' => 'medium',
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('home')->with('success', 'Ticket submitted successfully!');
    }
}