<?php

namespace App\Http\Controllers;

use App\Jobs\ExecuteWorkflowAction;
use App\Models\Lead;
use App\Models\LeadForm;
use App\Models\Contact;
use App\Models\Workflow;
use App\Services\LeadScoringService;
use Illuminate\Http\Request;

class LeadFormController extends Controller
{
    protected $leadScoringService;

    public function __construct(LeadScoringService $leadScoringService)
    {
        $this->leadScoringService = $leadScoringService;
    }

    public function submit(Request $request, LeadForm $leadForm)
    {
        $validatedData = $request->validate($this->getValidationRules($leadForm));

        $contact = $this->createOrUpdateContact($validatedData);

        $lead = Lead::create([
            'status' => 'new',
            'source' => 'landing_page',
            'contact_id' => $contact->id,
            'user_id' => $leadForm->landingPage->campaign->user_id,
            'potential_value' => $validatedData['potential_value'] ?? null,
            'expected_close_date' => $validatedData['expected_close_date'] ?? null,
            'lifecycle_stage' => 'lead',
        ]);

        // Score the new lead
        $this->leadScoringService->scoreLeads($lead);

        // Trigger workflow actions
        $this->triggerWorkflow($lead);

        return response()->json(['message' => 'Form submitted successfully', 'lead_id' => $lead->id]);
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
        $contact = Contact::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'company_size' => $data['company_size'] ?? null,
                'industry' => $data['industry'] ?? null,
            ]
        );
        return $contact;
    }

    private function triggerWorkflow(Lead $lead)
    {
        $workflows = Workflow::whereJsonContains('triggers->type', 'lead_created')->get();

        foreach ($workflows as $workflow) {
            $this->executeWorkflowActions($workflow, $lead);
        }
    }

    private function executeWorkflowActions(Workflow $workflow, Lead $lead)
    {
        foreach ($workflow->actions as $action) {
            ExecuteWorkflowAction::dispatch($action, $lead);
        }
    }
}