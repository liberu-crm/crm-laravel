<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    /**
     * Upload a new document and attach it to the given model.
     *
     * @param  UploadedFile $file
     * @param  Model        $documentable  Any Eloquent model with a morphMany('documents') relation.
     * @param  array        $metadata      Optional metadata (title, description, tags, …).
     * @return Document
     */
    public function upload(UploadedFile $file, Model $documentable, array $metadata = []): Document
    {
        $path = $this->storeFile($file);

        return Document::create([
            'file_path'          => $path,
            'original_filename'  => $file->getClientOriginalName(),
            'mime_type'          => $file->getMimeType(),
            'size'               => $file->getSize(),
            'title'              => $metadata['title'] ?? $file->getClientOriginalName(),
            'description'        => $metadata['description'] ?? null,
            'tags'               => $metadata['tags'] ?? null,
            'version'            => 1,
            'documentable_id'    => $documentable->getKey(),
            'documentable_type'  => get_class($documentable),
        ]);
    }

    /**
     * Upload a new version of an existing document.
     *
     * @param  UploadedFile $file
     * @param  Document     $document  The document to create a new version for.
     * @param  array        $metadata  Optional metadata overrides for the new version.
     * @return Document
     */
    public function uploadNewVersion(UploadedFile $file, Document $document, array $metadata = []): Document
    {
        $latestVersion = Document::where('documentable_id', $document->documentable_id)
            ->where('documentable_type', $document->documentable_type)
            ->max('version') ?? 0;

        $path = $this->storeFile($file);

        return Document::create([
            'file_path'          => $path,
            'original_filename'  => $file->getClientOriginalName(),
            'mime_type'          => $file->getMimeType(),
            'size'               => $file->getSize(),
            'title'              => $metadata['title'] ?? $document->title ?? $file->getClientOriginalName(),
            'description'        => $metadata['description'] ?? $document->description,
            'tags'               => $metadata['tags'] ?? $document->tags,
            'version'            => $latestVersion + 1,
            'documentable_id'    => $document->documentable_id,
            'documentable_type'  => $document->documentable_type,
        ]);
    }

    /**
     * Get a temporary download URL (or a streamed response) for a document.
     *
     * @param  Document $document
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Document $document)
    {
        $filename = $document->original_filename ?? basename($document->file_path);
        return Storage::download($document->file_path, $filename);
    }

    /**
     * Delete a document from storage and the database.
     */
    public function delete(Document $document): void
    {
        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $document->delete();
    }

    /**
     * Retrieve all versions of a document (all documents sharing the same
     * documentable, ordered newest-first).
     *
     * @return \Illuminate\Database\Eloquent\Collection<Document>
     */
    public function getVersions(Document $document)
    {
        return Document::where('documentable_id', $document->documentable_id)
            ->where('documentable_type', $document->documentable_type)
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Search documents belonging to a specific model by title, description, or filename.
     *
     * @param  Model  $documentable
     * @param  string $query
     * @return \Illuminate\Database\Eloquent\Collection<Document>
     */
    public function search(Model $documentable, string $query)
    {
        $likeQuery = '%' . $query . '%';

        return Document::where('documentable_id', $documentable->getKey())
            ->where('documentable_type', get_class($documentable))
            ->where(function ($q) use ($likeQuery) {
                $q->where('title', 'like', $likeQuery)
                  ->orWhere('description', 'like', $likeQuery)
                  ->orWhere('original_filename', 'like', $likeQuery)
                  ->orWhere('tags', 'like', $likeQuery);
            })
            ->orderByDesc('version')
            ->get();
    }

    /**
     * List all documents for a given model, optionally filtered by MIME type or tags.
     *
     * @param  Model  $documentable
     * @param  array  $filters  Supported keys: mime_type, tag
     * @return \Illuminate\Database\Eloquent\Collection<Document>
     */
    public function list(Model $documentable, array $filters = [])
    {
        $query = Document::where('documentable_id', $documentable->getKey())
            ->where('documentable_type', get_class($documentable));

        if (!empty($filters['mime_type'])) {
            $query->where('mime_type', $filters['mime_type']);
        }

        if (!empty($filters['tag'])) {
            $query->where('tags', 'like', '%' . $filters['tag'] . '%');
        }

        return $query->orderByDesc('created_at')->get();
    }

    // -------------------------------------------------------------------------

    /**
     * Store the file and return its storage path.
     */
    private function storeFile(UploadedFile $file): string
    {
        $directory = 'documents/' . date('Y/m');
        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($directory, $filename);
    }
}
