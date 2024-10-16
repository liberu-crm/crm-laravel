<?php

namespace App\Http\Controllers;

use App\Models\FormBuilder;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function createCustomField()
    {
        return view('custom-fields.create');
    }

    public function storeCustomField(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['text', 'number', 'date', 'boolean'])],
            'model_type' => ['required', Rule::in(['contact', 'lead'])],
        ]);

        $validated['team_id'] = auth()->user()->currentTeam->id;

        CustomField::create($validated);

        return redirect()->route('custom-fields.index')->with('success', 'Custom field created successfully.');
    }

    public function indexCustomFields()
    {
        $customFields = CustomField::where('team_id', auth()->user()->currentTeam->id)->get();
        return view('custom-fields.index', compact('customFields'));
    }

    public function editCustomField(CustomField $customField)
    {
        return view('custom-fields.edit', compact('customField'));
    }

    public function updateCustomField(Request $request, CustomField $customField)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['text', 'number', 'date', 'boolean'])],
            'model_type' => ['required', Rule::in(['contact', 'lead'])],
        ]);

        $customField->update($validated);

        return redirect()->route('custom-fields.index')->with('success', 'Custom field updated successfully.');
    }

    public function destroyCustomField(CustomField $customField)
    {
        $customField->delete();

        return redirect()->route('custom-fields.index')->with('success', 'Custom field deleted successfully.');
    }
}