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
            'triggers' => ['required', 'json', function ($_attr, $value, $fail) {
                $data = json_decode($value, true);
                if (! is_array($data) || empty($data)) {
                    $fail('Triggers must be a valid non-empty JSON object.');
                }
            }],
            'actions' => ['required', 'json', function ($_attr, $value, $fail) {
                $data = json_decode($value, true);
                if (! is_array($data) || empty($data)) {
                    $fail('Actions must be a valid non-empty JSON array.');
                }
            }],
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
            'triggers' => ['json', function ($_attr, $value, $fail) {
                $data = json_decode($value, true);
                if (! is_array($data) || empty($data)) {
                    $fail('Triggers must be a valid non-empty JSON object.');
                }
            }],
            'actions' => ['json', function ($_attr, $value, $fail) {
                $data = json_decode($value, true);
                if (! is_array($data) || empty($data)) {
                    $fail('Actions must be a valid non-empty JSON array.');
                }
            }],
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
