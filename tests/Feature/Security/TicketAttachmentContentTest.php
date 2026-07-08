<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Rules\ContentMimeType;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TicketAttachmentContentTest extends TestCase
{
    private const ALLOWED = ['image/png', 'image/jpeg', 'application/pdf'];

    private function fails(UploadedFile $file): bool
    {
        $failed = false;
        (new ContentMimeType(self::ALLOWED))->validate(
            'attachment',
            $file,
            function () use (&$failed): void {
                $failed = true;
            },
        );

        return $failed;
    }

    public function test_real_pdf_content_passes(): void
    {
        $file = UploadedFile::fake()->createWithContent('doc.pdf', "%PDF-1.4\n1 0 obj\n<< >>\nendobj\n");

        $this->assertFalse($this->fails($file));
    }

    public function test_text_content_disguised_as_png_is_rejected(): void
    {
        // Declared name is .png but the bytes are plain text — the spoof this rule blocks.
        $file = UploadedFile::fake()->createWithContent('evil.png', 'this is just plain text, not an image');

        $this->assertTrue($this->fails($file));
    }

    public function test_missing_upload_is_a_noop(): void
    {
        $failed = false;
        (new ContentMimeType(self::ALLOWED))->validate(
            'attachment',
            null,
            function () use (&$failed): void {
                $failed = true;
            },
        );

        $this->assertFalse($failed);
    }
}
