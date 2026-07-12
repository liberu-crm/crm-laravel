<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\LandingPage;
use App\Models\LeadForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadFormControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createForm(array $fields): LeadForm
    {
        $landingPage = LandingPage::factory()->create();

        return LeadForm::factory()->create([
            'landing_page_id' => $landingPage->id,
            'fields' => $fields,
        ]);
    }

    public function test_submits_valid_data_and_creates_lead(): void
    {
        $form = $this->createForm([
            ['name' => 'name', 'validation' => 'required|string', 'type' => 'text'],
            ['name' => 'email', 'validation' => 'required|email', 'type' => 'email'],
        ]);

        $response = $this->postJson("/forms/{$form->id}/submit", [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('contacts', ['email_hash' => Contact::hashEmail('john@example.com')]);
        $this->assertDatabaseHas('leads', ['status' => 'new']);
    }

    public function test_rejects_missing_required_field(): void
    {
        $form = $this->createForm([
            ['name' => 'email', 'validation' => 'required|email', 'type' => 'email'],
        ]);

        $response = $this->postJson("/forms/{$form->id}/submit", []);

        $response->assertStatus(422);
    }

    public function test_accepts_nullable_field_when_absent(): void
    {
        $form = $this->createForm([
            ['name' => 'name', 'validation' => 'required|string', 'type' => 'text'],
            ['name' => 'email', 'validation' => 'required|email', 'type' => 'email'],
            ['name' => 'phone', 'validation' => 'nullable|string', 'type' => 'text'],
        ]);

        $response = $this->postJson("/forms/{$form->id}/submit", [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);
    }

    public function test_strips_regex_rule(): void
    {
        $form = $this->createForm([
            ['name' => 'name', 'validation' => 'required|string', 'type' => 'text'],
            ['name' => 'email', 'validation' => 'required|email', 'type' => 'email'],
            ['name' => 'code', 'validation' => 'required|regex:/^[A-Z]+$/', 'type' => 'text'],
        ]);

        $response = $this->postJson("/forms/{$form->id}/submit", [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'code' => 'abc123',
        ]);

        $response->assertStatus(200);
    }

    public function test_strips_class_based_rule(): void
    {
        $form = $this->createForm([
            ['name' => 'name', 'validation' => 'required|string', 'type' => 'text'],
            ['name' => 'email', 'validation' => 'required|email', 'type' => 'email'],
            ['name' => 'field', 'validation' => 'required|App\Rules\CustomRule', 'type' => 'text'],
        ]);

        $response = $this->postJson("/forms/{$form->id}/submit", [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'field' => 'anything',
        ]);

        $response->assertStatus(200);
    }

    public function test_falls_back_to_required_when_all_rules_stripped(): void
    {
        $form = $this->createForm([
            ['name' => 'name', 'validation' => 'regex:/^[A-Z]+$/', 'type' => 'email'],
        ]);

        $response = $this->postJson("/forms/{$form->id}/submit", [
            'name' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_mixed_rules_safe_rules_still_apply(): void
    {
        $form = $this->createForm([
            ['name' => 'name', 'validation' => 'required|string|regex:/^[a-z]+$/|max:255', 'type' => 'text'],
            ['name' => 'email', 'validation' => 'required|email', 'type' => 'email'],
        ]);

        $response = $this->postJson("/forms/{$form->id}/submit", [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_contact_created_with_name(): void
    {
        $form = $this->createForm([
            ['name' => 'name', 'validation' => 'required|string', 'type' => 'text'],
            ['name' => 'email', 'validation' => 'required|email', 'type' => 'email'],
        ]);

        $response = $this->postJson("/forms/{$form->id}/submit", [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('contacts', [
            'email_hash' => Contact::hashEmail('jane@example.com'),
            'name' => 'Jane Smith',
        ]);
        $this->assertDatabaseHas('leads', [
            'status' => 'new',
            'contact_id' => Contact::where('email_hash', Contact::hashEmail('jane@example.com'))->first()->id,
        ]);
    }
}
