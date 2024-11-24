<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'subject' => 'required|max:255',
            'body' => 'required',
        ]);

        $user = Auth::user();
        $ticket = Ticket::create([
            'subject' => $validatedData['subject'],
            'body' => $validatedData['body'],
            'status' => 'open',
            'priority' => 'medium',
            'user_id' => $user->id,
        ]);

        return redirect()->route('home')->with('success', 'Ticket submitted successfully!');
    }
}