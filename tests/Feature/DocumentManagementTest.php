<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\Document;
use App\Services\DocumentService;
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

    public function test_rejects_php_file()
    {
        Storage::fake('local');
        $service = app(DocumentService::class);
        $contact = Contact::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'shell.php',
            '<?php system($_GET["cmd"]); ?>'
        );

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->upload($file, $contact);
    }

    public function test_rejects_mime_spoofing_php_content_with_pdf_extension()
    {
        Storage::fake('local');
        $service = app(DocumentService::class);
        $contact = Contact::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'document.pdf',
            '<?php system($_GET["cmd"]); ?>'
        );

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->upload($file, $contact);
    }

    public function test_accepts_valid_pdf()
    {
        Storage::fake('local');
        $service = app(DocumentService::class);
        $contact = Contact::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'contract.pdf',
            '%PDF-1.4'
        );

        $document = $service->upload($file, $contact);

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        $this->assertEquals('application/pdf', $document->mime_type);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_accepts_valid_png_image()
    {
        Storage::fake('local');
        $service = app(DocumentService::class);
        $contact = Contact::factory()->create();

        $file = UploadedFile::fake()->image('photo.png');

        $document = $service->upload($file, $contact);

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        $this->assertEquals('image/png', $document->mime_type);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_accepts_valid_csv()
    {
        Storage::fake('local');
        $service = app(DocumentService::class);
        $contact = Contact::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'data.csv',
            "name,email\nJohn,john@example.com"
        );

        $document = $service->upload($file, $contact);

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        $this->assertContains($document->mime_type, ['text/plain', 'text/csv']);
    }

    public function test_stores_file_without_user_supplied_extension()
    {
        Storage::fake('local');
        $service = app(DocumentService::class);
        $contact = Contact::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'exploit.php.exe',
            '%PDF-1.4'
        );

        $document = $service->upload($file, $contact);

        $this->assertStringEndsWith('.pdf', $document->file_path);
    }

    public function test_config_allowed_mimes_contains_common_document_types()
    {
        $allowed = config('documents.allowed_mimes');

        $this->assertContains('application/pdf', $allowed);
        $this->assertContains('application/msword', $allowed);
        $this->assertContains('image/png', $allowed);
        $this->assertContains('application/vnd.oasis.opendocument.text', $allowed);
        $this->assertContains('image/bmp', $allowed);
        $this->assertNotContains('application/x-httpd-php', $allowed);
        $this->assertNotContains('text/x-php', $allowed);
    }
}
