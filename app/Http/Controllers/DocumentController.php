<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'documentable_id' => 'required|integer',
            'documentable_type' => 'required|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents');

        $document = new Document([
            'file_path' => $path,
            'version' => 1,
            'documentable_id' => $request->documentable_id,
            'documentable_type' => $request->documentable_type,
        ]);

        $document->save();

        return response()->json(['message' => 'Document uploaded successfully', 'document' => $document]);
    }

    public function download(Document $document)
    {
        return Storage::download($document->file_path);
    }

    public function newVersion(Request $request, Document $document)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('documents');

        $newDocument = new Document([
            'file_path' => $path,
            'version' => $document->version + 1,
            'documentable_id' => $document->documentable_id,
            'documentable_type' => $document->documentable_type,
        ]);

        $newDocument->save();

        return response()->json(['message' => 'New version uploaded successfully', 'document' => $newDocument]);
    }

    public function getVersions(Request $request)
    {
        $request->validate([
            'documentable_id' => 'required|integer',
            'documentable_type' => 'required|string',
        ]);

        $versions = Document::where('documentable_id', $request->documentable_id)
            ->where('documentable_type', $request->documentable_type)
            ->orderBy('version', 'desc')
            ->get();

        return response()->json(['versions' => $versions]);
    }
}