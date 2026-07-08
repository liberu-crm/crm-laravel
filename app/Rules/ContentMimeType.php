<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Server-side content-type guard for uploads: detects the file's REAL MIME from
 * its bytes (Finfo) and fails unless it is in the allowed list. Declared/browser
 * MIME (Filament's acceptedFileTypes) is spoofable; this enforces by content.
 */
class ContentMimeType implements ValidationRule
{
    /**
     * @param  array<int, string>  $allowed
     */
    public function __construct(private array $allowed) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Optional upload / non-file value: nothing to validate.
        if (! $value instanceof UploadedFile) {
            return;
        }

        $path = $value->getRealPath();
        if ($path === false || $path === '') {
            $fail('The :attribute could not be read for validation.');

            return;
        }

        $detected = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (! in_array($detected, $this->allowed, true)) {
            $fail('The :attribute has a disallowed file content type.');
        }
    }
}
