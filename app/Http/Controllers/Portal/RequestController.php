<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Request as DocumentRequest;
use App\Models\Document;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RequestController extends Controller
{
    public function create(Request $request)
    {
        $document = null;

        if ($docId = $request->input('document')) {
            $document = Document::where('document_id', $docId)->first();
        }

        $documents = Document::where('is_public', true)
            ->where('status', 'published')
            ->orderBy('session_name')
            ->get();

        return view('public.requests.create', compact('document', 'documents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requester_name' => 'required|string|max:255',
            'contact_number' => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'email'          => 'nullable|email|max:255',
            'document_id'    => 'required|string',
            'copies'         => 'required|integer|min:1',
            'reason'         => 'required|string|max:255',
            'pickup_date'    => 'required|date',
            'valid_id'       => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'selfie'         => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Generate a private token. Only its hash is stored in the database.
        $accessToken = Str::random(64);

        $idPath = null;
        $selfiePath = null;

        if ($request->hasFile('valid_id')) {
            $idPath = $request->file('valid_id')->store('valid_ids', 'public');
        }

        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('selfies', 'public');
        }

        try {
            $newRequest = DocumentRequest::create([
                'request_id'        => 'REQ-' . strtoupper(Str::random(10)),
                'requester_name'    => $validated['requester_name'],
                'contact_number'    => $validated['contact_number'],
                'email'             => $validated['email'] ?? null,
                'document_id'       => $validated['document_id'],
                'copies'            => $validated['copies'],
                'reason'             => $validated['reason'],
                'pickup_date'       => $validated['pickup_date'],
                'valid_id_path'     => $idPath,
                'selfie_path'       => $selfiePath,
                'status'            => 'Pending',
                'access_token_hash' => hash('sha256', $accessToken),
            ]);
        } catch (\Throwable $e) {
            if ($idPath && Storage::disk('public')->exists($idPath)) {
                Storage::disk('public')->delete($idPath);
            }

            if ($selfiePath && Storage::disk('public')->exists($selfiePath)) {
                Storage::disk('public')->delete($selfiePath);
            }

            throw $e;
        }

        // Keep the plain token in the session only for the confirmation page.
        session()->put('request_access_token_' . $newRequest->id, $accessToken);

        $notificationBody = "Your request ({$newRequest->request_id}) has been received. We will notify you once it is ready for pickup.";

        // Notifications must not turn a successfully saved request into a 500 page.
        try {
            app(SmsService::class)->send(
                $validated['contact_number'],
                "Bontoc SB Portal: {$notificationBody}"
            );
        } catch (\Throwable $e) {
            report($e);
        }

        if (!empty($validated['email'])) {
            try {
                app(EmailService::class)->send(
                    $validated['email'],
                    $validated['requester_name'],
                    "Request {$newRequest->request_id} — Received",
                    $notificationBody
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('public.requests.success', $newRequest->id);
    }

    public function index(Request $request)
    {
        // Only list requests this visitor submitted in their own session.
        $prefix = 'request_access_token_';

        $ownedIds = collect(array_keys(session()->all()))
            ->filter(fn ($key) => str_starts_with($key, $prefix))
            ->map(fn ($key) => (int) substr($key, strlen($prefix)))
            ->filter()
            ->values()
            ->all();

        $requests = DocumentRequest::with('document')
            ->whereIn('id', $ownedIds)
            ->latest('id')
            ->paginate(10);

        return view('public.requests.index', compact('requests'));
    }

    public function cancel(DocumentRequest $request)
    {
        // A visitor may only cancel a request submitted in their own session.
        abort_unless($this->ownsRequest($request), 403, 'You can only cancel your own requests.');

        if ($request->status === 'Pending') {
            $request->update(['status' => 'Cancelled']);
        }

        return back();
    }

    public function destroy(DocumentRequest $request)
    {
        // A visitor may only delete a request submitted in their own session.
        abort_unless($this->ownsRequest($request), 403, 'You can only delete your own requests.');

        $idPath = $request->valid_id_path;
        $selfiePath = $request->selfie_path;
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($request));

        $request->delete();

        // Remove the uploaded valid ID and selfie only when the record is permanently deleted.
        if (!$usesSoftDeletes && $idPath && Storage::disk('public')->exists($idPath)) {
            Storage::disk('public')->delete($idPath);
        }

        if (!$usesSoftDeletes && $selfiePath && Storage::disk('public')->exists($selfiePath)) {
            Storage::disk('public')->delete($selfiePath);
        }

        session()->forget('request_access_token_' . $request->id);

        return back();
    }

    public function success($id)
    {
        $documentRequest = DocumentRequest::findOrFail($id);
        $sessionKey = 'request_access_token_' . $documentRequest->id;
        $accessToken = session()->get($sessionKey);

        // Only show the confirmation page to the visitor who submitted the request.
        abort_unless($accessToken, 403, 'This confirmation page is only available in the browser session where the request was submitted.');

        return view('public.requests.success', [
            'request' => $documentRequest,
            'accessToken' => $accessToken,
        ]);
    }

    /**
     * True when this visitor's session holds the private token issued
     * when the request was submitted.
     */
    private function ownsRequest(DocumentRequest $documentRequest): bool
    {
        return session()->has('request_access_token_' . $documentRequest->id);
    }
}