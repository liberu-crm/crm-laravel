<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadForm;
use Illuminate\Http\Request;

class LeadFormController extends Controller
{
    public function submit(Request $request, LeadForm $leadForm)
    {
        $validatedData = $request->validate($this->getValidationRules($leadForm));

        $lead = Lead::create([
            'status' => 'new',
            'source' => 'landing_page',
            'contact_id' => $this->createOrUpdateContact($validatedData),
            'user_id' => $leadForm->landingPage->campaign->user_id,
        ]);

        // Trigger workflow actions
        // TODO: Implement workflow triggering

        return response()->json(['message' => 'Form submitted successfully']);
    }

    private function getValidationRules(LeadForm $leadForm): array
    {
        $rules = [];
        foreach ($leadForm->fields as $field) {
            $rules[$field['name']] = $field['validation'] ?? 'required';
        }
        return $rules;
    }

    private function createOrUpdateContact(array $data)
    {
        // TODO: Implement contact creation or update logic
        // This should create a new contact or update an existing one based on the email address
        // Return the contact ID
    }
}