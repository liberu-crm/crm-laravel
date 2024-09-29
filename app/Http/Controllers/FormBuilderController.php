<?php

namespace App\Http\Controllers;

use App\Models\FormBuilder;
use Illuminate\Http\Request;

class FormBuilderController extends Controller
{
    public function index()
    {
        $forms = FormBuilder::where('team_id', auth()->user()->currentTeam->id)->get();
        return view('form-builders.index', compact('forms'));
    }

    public function create()
    {
        return view('form-builders.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array',
        ]);

        $validated['team_id'] = auth()->user()->currentTeam->id;

        FormBuilder::create($validated);

        return redirect()->route('form-builders.index')->with('success', 'Form created successfully.');
    }

    public function edit(FormBuilder $formBuilder)
    {
        return view('form-builders.edit', compact('formBuilder'));
    }

    public function update(Request $request, FormBuilder $formBuilder)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array',
        ]);

        $formBuilder->update($validated);

        return redirect()->route('form-builders.index')->with('success', 'Form updated successfully.');
    }

    public function destroy(FormBuilder $formBuilder)
    {
        $formBuilder->delete();

        return redirect()->route('form-builders.index')->with('success', 'Form deleted successfully.');
    }
}