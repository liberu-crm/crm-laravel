<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\FormBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FormBuilderController extends Controller
{
    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {

        $user = Auth::user();
        $forms = FormBuilder::where('team_id', $user->currentTeam->id)->get();

        return view('form-builders.index', ['forms' => $forms]);
    }

    public function create(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('form-builders.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array',
        ]);

        $validated['team_id'] = $user->currentTeam->id;

        FormBuilder::create($validated);

        return redirect()->route('form-builders.index')->with('success', 'Form created successfully.');
    }

    public function edit(FormBuilder $formBuilder): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('form-builders.edit', ['formBuilder' => $formBuilder]);
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

    public function createCustomField(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('custom-fields.create');
    }

    public function storeCustomField(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['text', 'number', 'date', 'boolean'])],
            'model_type' => ['required', Rule::in(['contact', 'lead'])],
        ]);

        $validated['team_id'] = $user->currentTeam->id;

        CustomField::create($validated);

        return redirect()->route('custom-fields.index')->with('success', 'Custom field created successfully.');
    }

    public function indexCustomFields(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $user = Auth::user();
        $customFields = CustomField::where('team_id', $user->currentTeam->id)->get();

        return view('custom-fields.index', ['customFields' => $customFields]);
    }

    public function editCustomField(CustomField $customField): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('custom-fields.edit', ['customField' => $customField]);
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
