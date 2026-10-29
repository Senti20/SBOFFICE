<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public function create(array $data, UploadedFile $file, int $userId): Document
    {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($data, $file, $userId, &$storedPath) {
                $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $safeName = $safeName !== '' ? $safeName : 'document';
                $storedPath = $file->storeAs(
                    'documents/'.date('Y/m'),
                    $safeName.'-'.Str::lower(Str::random(12)).'.pdf',
                    'local'
                );

                $document = Document::create([
                    'document_number' => null,
                    'title' => $data['title'],
                    'document_type_id' => $data['document_type_id'] ?? null,
                    'session_number' => $data['session_number'] ?? null,
                    'document_date' => $data['document_date'] ?? null,
                    'author' => $data['author'] ?? null,
                    'tags' => $data['tags'] ?? null,
                    'file_path' => $storedPath,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: 'application/pdf',
                    'file_size' => $file->getSize() ?: 0,
                    'status' => 'published',
                    'is_public' => (bool) ($data['is_public'] ?? true),
                    'uploaded_by' => $userId,
                ]);

                $document->update([
                    'document_number' => 'DOC-'.str_pad((string) $document->id, 3, '0', STR_PAD_LEFT),
                ]);

                return $document->fresh(['type', 'uploader']);
            });
        } catch (\Throwable $e) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            throw $e;
        }
    }

    public function deleteFile(Document $document): void
    {
        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }
    }
}
