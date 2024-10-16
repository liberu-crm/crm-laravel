<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class LeadManagementUITest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ... (previous test methods remain unchanged)

    public function test_create_and_retrieve_lead_with_custom_fields()
    {
        $contact = Contact::factory()->create();
        $customFields = ['industry' => 'Technology', 'company_size' => '50-100'];

        $lead = Lead::factory()->create([
            'contact_id' => $contact->id,
            'custom_fields' => $customFields,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/leads/{$lead->id}");

        $response->assertStatus(200);
        $response->assertSee($contact->name);
        $response->assertSee('Technology');
        $response->assertSee('50-100');

        $this->assertEquals($customFields, $lead->fresh()->custom_fields);
    }

    // ... (remaining test methods)
}