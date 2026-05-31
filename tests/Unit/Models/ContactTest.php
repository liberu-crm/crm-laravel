<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $contact->company);
        $this->assertEquals($company->id, $contact->company->id);
    }

    public function test_contact_auto_associates_with_company(): void
    {
        $company = Company::factory()->create(['domain' => 'example.com']);
        $contact = Contact::factory()->create(['email' => 'john@example.com']);

        $this->assertEquals($company->id, $contact->company_id);
    }

    public function test_contact_lifecycle_stage_is_fillable(): void
    {
        $contact = Contact::factory()->create(['lifecycle_stage' => 'lead']);

        $this->assertEquals('lead', $contact->lifecycle_stage);
    }
}
