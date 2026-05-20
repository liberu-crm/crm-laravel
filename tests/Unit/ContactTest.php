<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_can_be_created_with_new_fields(): void
    {
        $contact = Contact::factory()->create([
            'status' => 'active',
            'source' => 'website',
            'industry' => 'Technology',
            'company_size' => 100,
            'annual_revenue' => 1000000,
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => 'active',
            'source' => 'website',
            'industry' => 'Technology',
            'company_size' => 100,
            'annual_revenue' => 1000000,
        ]);
    }

    public function test_contact_has_many_deals(): void
    {
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create(['contact_id' => $contact->id]);

        $this->assertTrue($contact->deals->contains($deal));
    }

    public function test_contact_has_many_activities(): void
    {
        $contact = Contact::factory()->create();
        $activity = Activity::factory()->create([
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $this->assertTrue($contact->activities->contains($activity));
    }
}