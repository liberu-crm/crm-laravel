<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->has('potential_value_min')) {
            $query->where('potential_value', '>=', $request->input('potential_value_min'));
        }

        if ($request->has('potential_value_max')) {
            $query->where('potential_value', '<=', $request->input('potential_value_max'));
        }

        $leads = $query->paginate(15);

        return view('leads.index', compact('leads'));
    }

    // Other CRUD methods...
}