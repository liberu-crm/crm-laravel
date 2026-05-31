<?php

namespace Tests\Unit\Services;

use App\Models\Contact;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentService;
        Storage::fake('local');

        // Allow the MIME types that Finfo will actually detect for test files
        config(['documents.allowed_mimes' => [
            'application/pdf',
            'application/octet-stream',
            'text/plain',
            'application/x-empty',
            'inode/x-empty',
        ]]);
        config(['documents.extension_map' => [
            'application/pdf' => 'pdf',
            'application/octet-stream' => 'bin',
            'text/plain' => 'txt',
            'application/x-empty' => 'bin',
            'inode/x-empty' => 'bin',
        ]]);
    }

    public function test_upload_stores_file_and_creates_document(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $contact = Contact::factory()->create(['team_id' => $user->currentTeam->id]);
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $document = $this->service->upload($file, $contact, ['title' => 'My Contract']);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('My Contract', $document->title);
        $this->assertEquals($contact->id, $document->documentable_id);
        $this->assertEquals(Contact::class, $document->documentable_type);
        $this->assertEquals(1, $document->version);
    }

    public function test_upload_uses_filename_as_default_title(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $contact = Contact::factory()->create(['team_id' => $user->currentTeam->id]);
        $file = UploadedFile::fake()->create('report.pdf', 50, 'application/pdf');

        $document = $this->service->upload($file, $contact);

        $this->assertEquals('report.pdf', $document->title);
    }

    public function test_upload_new_version_increments_version(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $contact = Contact::factory()->create(['team_id' => $user->currentTeam->id]);
        $file1 = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');
        $v1 = $this->service->upload($file1, $contact);

        $file2 = UploadedFile::fake()->create('contract_v2.pdf', 110, 'application/pdf');
        $v2 = $this->service->uploadNewVersion($file2, $v1);

        $this->assertEquals(2, $v2->version);
        $this->assertEquals($contact->id, $v2->documentable_id);
    }

    public function test_delete_removes_document_from_database(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $contact = Contact::factory()->create(['team_id' => $user->currentTeam->id]);
        $file = UploadedFile::fake()->create('to_delete.pdf', 50, 'application/pdf');

        $document = $this->service->upload($file, $contact);

        $this->service->delete($document);

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }
}
