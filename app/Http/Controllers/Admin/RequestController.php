<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Request as DocumentRequest;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RequestController extends Controller
{
    /**
     * Display requests for authorized staff.
     */
    public function index(): View
    {
        $statuses = [
            'Pending',
            'Processing',
            'Ready for Pickup',
            'Completed',
            'Cancelled',
            'Rejected',
        ];

        $requests = DocumentRequest::query()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.requests.index', compact(
            'requests',
            'statuses'
        ));
    }

    /**
     * Update the status of a request.
     */
    public function update(
        HttpRequest $request,
        DocumentRequest $documentRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:Pending,Processing,Ready for Pickup,Completed,Cancelled,Rejected',
            ],
            'fee' => 'nullable|numeric|min:0|max:99999999.99',
        ]);

        $oldStatus = $documentRequest->status;
        $oldFee = $documentRequest->fee !== null ? round((float) $documentRequest->fee, 2) : null;

        $documentRequest->status = $validated['status'];

        // The fee is only changed when the form actually sends the fee field.
        if ($request->has('fee')) {
            $documentRequest->fee = $validated['fee'] ?? null;
        }

        $newFee = $documentRequest->fee !== null ? round((float) $documentRequest->fee, 2) : null;

        $documentRequest->save();

        $statusChanged = $oldStatus !== $documentRequest->status;
        $feeChanged = $oldFee !== $newFee;

        // Email the requester when the status changed, or when the fee was
        // changed on a request that is already Ready for Pickup.
        if ($statusChanged || ($feeChanged && $documentRequest->status === 'Ready for Pickup')) {
            $this->notifyRequester($documentRequest);
        }

        return redirect()
            ->route('admin.requests.index')
            ->with('success', 'Request status updated successfully.');
    }

    /**
     * Send the requester an email about their request's new status.
     * A failed email must never turn a saved status change into an error page.
     */
    private function notifyRequester(DocumentRequest $documentRequest): void
    {
        $reference = $documentRequest->request_id;

        $feeText = $documentRequest->fee !== null
            ? ' Fee to pay upon pickup: PHP ' . number_format((float) $documentRequest->fee, 2) . '.'
            : " The fee will be confirmed at the SB Secretary's office.";

        $messages = [
            'Pending'          => "Your request ({$reference}) is pending review.",
            'Processing'       => "Your request ({$reference}) has been accepted and is now being processed.",
            'Ready for Pickup' => "Your request ({$reference}) is ready for pickup."
                . ($documentRequest->pickup_date
                    ? ' Preferred pickup date: ' . $documentRequest->pickup_date->format('F d, Y') . '.'
                    : '')
                . $feeText
                . ' Please bring your valid ID.',
            'Completed'        => "Your request ({$reference}) has been completed. Thank you.",
            'Cancelled'        => "Your request ({$reference}) has been cancelled.",
            'Rejected'         => "Your request ({$reference}) has been rejected. Please contact the SB Secretary's office for details.",
        ];

        $body = $messages[$documentRequest->status]
            ?? "The status of your request ({$reference}) is now {$documentRequest->status}.";

        // Send SMS notification to the requester's contact number.
        if (!empty($documentRequest->contact_number)) {
            try {
                app(SmsService::class)->send(
                    $documentRequest->contact_number,
                    "Bontoc SB Portal: {$body}"
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Send email notification if the requester provided an email address.
        if (!empty($documentRequest->email)) {
            try {
                app(EmailService::class)->send(
                    $documentRequest->email,
                    $documentRequest->requester_name,
                    "Request {$reference} — {$documentRequest->status}",
                    $body
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Show the valid ID uploaded with a request. Admin-only (see routes).
     */
    public function validId(DocumentRequest $documentRequest)
    {
        $path = $documentRequest->valid_id_path;

        abort_unless(
            $path && Storage::disk('public')->exists($path),
            404,
            'The uploaded ID file could not be found.'
        );

        return Storage::disk('public')->response($path);
    }

    /**
     * Show the selfie uploaded with a request. Admin-only (see routes).
     */
    public function selfie(DocumentRequest $documentRequest)
    {
        $path = $documentRequest->selfie_path;

        abort_unless(
            $path && Storage::disk('public')->exists($path),
            404,
            'The uploaded selfie could not be found.'
        );

        return Storage::disk('public')->response($path);
    }

    /**
     * Permanently delete a finished request and its uploaded files.
     * Only Completed, Cancelled or Rejected requests may be deleted.
     */
    public function destroy(DocumentRequest $documentRequest): RedirectResponse
    {
        if (! in_array($documentRequest->status, ['Completed', 'Cancelled', 'Rejected'], true)) {
            return redirect()
                ->route('admin.requests.index')
                ->with('error', 'Only Completed, Cancelled or Rejected requests can be deleted.');
        }

        $reference = $documentRequest->request_id;
        $idPath = $documentRequest->valid_id_path;
        $selfiePath = $documentRequest->selfie_path;

        $documentRequest->delete();

        // Remove the uploaded valid ID and selfie along with the record.
        foreach ([$idPath, $selfiePath] as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()
            ->route('admin.requests.index')
            ->with('success', "Request {$reference} was deleted.");
    }
}