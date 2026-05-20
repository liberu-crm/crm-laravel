<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_document_can_be_created_for_contact()
    {
        $contact = Contact::factory()->create();
        Storage::fake('public');

        $document = Document::factory()->create([
            'documentable_id' => $contact->id,
            'documentable_type' => get_class($contact),
        ]);

        $this->assertDatabaseHas('documents', [
            'documentable_id' => $contact->id,
            'documentable_type' => get_class($contact),
        ]);
    }

    public function test_document_can_be_created_for_lead()
    {
        $lead = Lead::factory()->create();

        $document = Document::factory()->create([
            'documentable_id' => $lead->id,
            'documentable_type' => get_class($lead),
        ]);

        $this->assertDatabaseHas('documents', [
            'documentable_id' => $lead->id,
            'documentable_type' => get_class($lead),
        ]);
    }

    public function test_document_can_be_created_for_deal()
    {
        $deal = Deal::factory()->create();

        $document = Document::factory()->create([
            'documentable_id' => $deal->id,
            'documentable_type' => get_class($deal),
        ]);

        $this->assertDatabaseHas('documents', [
            'documentable_id' => $deal->id,
            'documentable_type' => get_class($deal),
        ]);
    }

    public function test_document_versions_can_be_tracked()
    {
        $contact = Contact::factory()->create();
        $documents = Document::factory()->count(3)->create([
            'documentable_id' => $contact->id,
            'documentable_type' => get_class($contact),
        ]);

        $count = Document::where([
            'documentable_id' => $contact->id,
            'documentable_type' => get_class($contact),
        ])->count();

        $this->assertEquals(3, $count);
    }

    public function test_document_has_morphable_relationship()
    {
        $contact = Contact::factory()->create();
        $document = Document::factory()->create([
            'documentable_id' => $contact->id,
            'documentable_type' => get_class($contact),
        ]);

        $this->assertEquals($contact->id, $document->documentable->id);
    }
}
