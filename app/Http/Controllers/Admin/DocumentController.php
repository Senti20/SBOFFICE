<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Document::withTrashed()->latest('id');

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

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $documents = $query->paginate(10)->withQueryString();
        $types = DocumentType::orderBy('name')->get();

        return view('admin.documents.index', compact('documents', 'types'));
    }

    public function create(): View
    {
        $types = DocumentType::orderBy('name')->get();

        return view('admin.documents.create', compact('types'));
    }

    public function store(
        Request $request,
        DocumentService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'session_name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'session_date' => ['required', 'date'],
            'sponsors' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['nullable', 'boolean'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $validated['is_public'] = $request->boolean('is_public');

        $file = $request->file('pdf');
        $path = null;

        try {
            /*
             * Generate an ID that is not already used.
             * withTrashed() also checks soft-deleted documents.
             */
            $number = 1;

            do {
                $docId = 'DOC-' . str_pad(
                    (string) $number,
                    3,
                    '0',
                    STR_PAD_LEFT
                );

                $exists = Document::withTrashed()
                    ->where('document_id', $docId)
                    ->exists();

                $number++;
            } while ($exists);

            $filename = $docId . '_' . $file->getClientOriginalName();

            $path = $file->storeAs(
                'documents/' . date('Y/m'),
                $filename,
                'local'
            );

            if (!$path) {
                throw new \RuntimeException(
                    'The uploaded PDF could not be saved.'
                );
            }

            $validated['document_id'] = $docId;
            $validated['file_path'] = $path;
            $validated['filename'] = $filename;
            $validated['status'] = 'published';

            unset($validated['pdf']);

            $document = Document::create($validated);

            return redirect()->route('admin.documents.index')
                ->with(
                    'success',
                    "{$document->document_id} was uploaded successfully."
                );
        } catch (\Throwable $e) {
            if ($path && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }

            report($e);

            return back()
                ->withInput($request->except('pdf'))
                ->with(
                    'error',
                    'The document could not be published. Please try again. If the problem continues, check the Laravel error log.'
                );
        }
    }

    public function edit($id): View
    {
        $document = Document::withTrashed()->findOrFail($id);
        $types = DocumentType::orderBy('name')->get();

        return view(
            'admin.documents.edit',
            compact('document', 'types')
        );
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $document = Document::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type_id' => ['required', 'string', 'max:100'],
            'session_number' => ['nullable', 'string', 'max:100'],
            'document_date' => ['required', 'date'],
            'author' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:published,archived'],
            'is_public' => ['nullable', 'boolean'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $newPath = null;
        $oldPath = $document->file_path;
        $filename = $document->filename;

        try {
            if ($request->hasFile('pdf')) {
                $file = $request->file('pdf');

                $newPath = $file->store(
                    'documents/' . date('Y/m'),
                    'local'
                );

                if (!$newPath) {
                    throw new \RuntimeException(
                        'The replacement PDF could not be saved.'
                    );
                }

                $filename = $file->getClientOriginalName();
            }

            if (
                $validated['status'] === 'published' &&
                $document->trashed()
            ) {
                $document->restore();
            }

            $document->update([
                'session_name' => $validated['title'],
                'type' => $validated['document_type_id'],
                'session_id' => $validated['session_number'] ?? null,
                'session_date' => $validated['document_date'],
                'sponsors' => $validated['author'] ?? null,
                'tags' => $validated['tags'] ?? null,
                'status' => $validated['status'],
                'is_public' => $request->boolean('is_public'),
                ...($newPath ? [
                    'file_path' => $newPath,
                    'filename' => $filename,
                ] : []),
            ]);

            if (
                $newPath &&
                $oldPath &&
                $oldPath !== $newPath
            ) {
                Storage::disk('local')->delete($oldPath);
            }
        } catch (\Throwable $e) {
            if (
                $newPath &&
                Storage::disk('local')->exists($newPath)
            ) {
                Storage::disk('local')->delete($newPath);
            }

            report($e);

            return back()
                ->withInput($request->except('pdf'))
                ->with(
                    'error',
                    'The document could not be updated. Please check the Laravel error log.'
                );
        }

        return redirect()->route('admin.documents.index')
            ->with(
                'success',
                "{$document->document_id} was updated."
            );
    }

    /**
     * Archive a document without deleting its database record or PDF.
     */
    public function archive($id): RedirectResponse
    {
        $document = Document::findOrFail($id);

        $document->update([
            'status' => 'archived',
            'is_public' => false,
        ]);

        return redirect()->route('admin.documents.index', [
            'status' => 'archived',
        ])->with(
            'success',
            "{$document->document_id} was archived successfully."
        );
    }

    /**
     * Permanently delete a document and its stored PDF.
     */
    public function destroy($id): RedirectResponse
    {
        $document = Document::withTrashed()->findOrFail($id);

        if (
            $document->file_path &&
            Storage::disk('local')->exists($document->file_path)
        ) {
            Storage::disk('local')->delete($document->file_path);
        }

        $documentId = $document->document_id;

        $document->forceDelete();

        return redirect()->route('admin.documents.index')
            ->with(
                'success',
                "{$documentId} was permanently deleted."
            );
    }

    /**
     * Open a document PDF from storage.
     */
    public function file($id): BinaryFileResponse
    {
        $document = Document::withTrashed()->findOrFail($id);

        abort_unless(
            $document->file_path &&
            Storage::disk('local')->exists($document->file_path),
            404
        );

        return response()->file(
            Storage::disk('local')->path($document->file_path),
            ['Content-Type' => 'application/pdf']
        );
    }
}
