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

    public function test_user_can_upload_document_for_contact()
    {
        $contact = Contact::factory()->create();
        Storage::fake('public');

        $response = $this->actingAs($this->user)->post('/documents/upload', [
            'file' => UploadedFile::fake()->create('document.pdf', 100),
            'documentable_id' => $contact->id,
            'documentable_type' => get_class($contact),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('documents', [
            'documentable_id' => $contact->id,
            'documentable_type' => get_class($contact),
        ]);
    }

    public function test_user_can_upload_document_for_lead()
    {
        $lead = Lead::factory()->create();
        Storage::fake('public');

        $response = $this->actingAs($this->user)->post('/documents/upload', [
            'file' => UploadedFile::fake()->create('document.pdf', 100),
            'documentable_id' => $lead->id,
            'documentable_type' => get_class($lead),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('documents', [
            'documentable_id' => $lead->id,
            'documentable_type' => get_class($lead),
        ]);
    }

    public function test_user_can_upload_document_for_deal()
    {
        $deal = Deal::factory()->create();
        Storage::fake('public');

        $response = $this->actingAs($this->user)->post('/documents/upload', [
            'file' => UploadedFile::fake()->create('document.pdf', 100),
            'documentable_id' => $deal->id,
            'documentable_type' => get_class($deal),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('documents', [
            'documentable_id' => $deal->id,
            'documentable_type' => get_class($deal),
        ]);
    }

    public function test_user_can_download_document()
    {
        $document = Document::factory()->create();

        $response = $this->actingAs($this->user)->get("/documents/{$document->id}/download");

        $response->assertStatus(200);
    }

    public function test_user_can_upload_new_version_of_document()
    {
        $document = Document::factory()->create();
        Storage::fake('public');

        $response = $this->actingAs($this->user)->post("/documents/{$document->id}/new-version", [
            'file' => UploadedFile::fake()->create('document_v2.pdf', 100),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('documents', [
            'documentable_id' => $document->documentable_id,
            'documentable_type' => $document->documentable_type,
            'version' => 2,
        ]);
    }

    public function test_user_can_get_document_versions()
    {
        $contact = Contact::factory()->create();
        Document::factory()->count(3)->create([
            'documentable_id' => $contact->id,
            'documentable_type' => get_class($contact),
        ]);

        $response = $this->actingAs($this->user)->get('/documents/versions', [
            'documentable_id' => $contact->id,
            'documentable_type' => get_class($contact),
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'versions');
    }
}