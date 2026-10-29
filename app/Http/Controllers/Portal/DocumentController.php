<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    /**
     * Display publicly available documents.
     */
    public function index(Request $request): View
    {
        // Only show documents that are published and public.
        $query = Document::query()
            ->where('is_public', true)
            ->where('status', 'published');

        // Search by document information.
        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($search) {
                $like = '%' . $search . '%';

                $builder->where('document_id', 'like', $like)
                    ->orWhere('session_name', 'like', $like)
                    ->orWhere('sponsors', 'like', $like)
                    ->orWhere('session_id', 'like', $like)
                    ->orWhere('tags', 'like', $like)
                    ->orWhere('type', 'like', $like);
            });
        }

        // Filter by document type.
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by session.
        if ($session = trim((string) $request->input('session'))) {
            $query->where('session_id', 'like', '%' . $session . '%');
        }

        // Filter by sponsor.
        if ($sponsor = trim((string) $request->input('sponsor'))) {
            $query->where('sponsors', 'like', '%' . $sponsor . '%');
        }

        // Sort and paginate results.
        $documents = $query
            ->latest('session_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $types = DocumentType::orderBy('name')->get();

        return view('public.index', compact('documents', 'types'));
    }

    /**
     * Display a PDF only when its document is public and published.
     */
    public function file(Document $document): BinaryFileResponse
    {
        // Prevent access to archived, private, or soft-deleted documents.
        abort_unless(
            $document->is_public
                && $document->status === 'published'
                && ! $document->trashed(),
            404
        );

        // Check that a file path exists and the PDF is stored.
        abort_unless(
            $document->file_path
                && Storage::disk('local')->exists($document->file_path),
            404
        );

        return response()->file(
            Storage::disk('local')->path($document->file_path),
            ['Content-Type' => $document->mime_type ?: 'application/pdf']
        );
    }
}
