<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index()
    {
        $workflows = Workflow::all();
        return response()->json($workflows);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'triggers' => 'required|json',
            'actions' => 'required|json',
        ]);

        $workflow = Workflow::create($validatedData);
        return response()->json($workflow, 201);
    }

    public function show(Workflow $workflow)
    {
        return response()->json($workflow);
    }

    public function update(Request $request, Workflow $workflow)
    {
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'triggers' => 'json',
            'actions' => 'json',
        ]);

        $workflow->update($validatedData);
        return response()->json($workflow);
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->delete();
        return response()->json(null, 204);
    }
}