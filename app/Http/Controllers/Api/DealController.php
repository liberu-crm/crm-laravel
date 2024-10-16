<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index()
    {
        return Deal::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'value' => 'required|numeric',
            'status' => 'required|string|in:open,closed,won,lost',
        ]);

        $deal = Deal::create($request->all());
        return response()->json($deal, 201);
    }

    public function show(Deal $deal)
    {
        return $deal;
    }

    public function update(Request $request, Deal $deal)
    {
        $request->validate([
            'title' => 'string|max:255',
            'value' => 'numeric',
            'status' => 'string|in:open,closed,won,lost',
        ]);

        $deal->update($request->all());
        return response()->json($deal, 200);
    }

    public function destroy(Deal $deal)
    {
        $deal->delete();
        return response()->json(null, 204);
    }
}