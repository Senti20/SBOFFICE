<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Request as DocumentRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_documents' => Document::count(),
            'public_documents' => Document::where('is_public', true)->where('status', 'published')->count(),
            'pending_requests' => DocumentRequest::where('status', 'Pending')->count(),
            'ready_requests' => DocumentRequest::where('status', 'Ready for Pickup')->count(),
            'completed_requests' => DocumentRequest::where('status', 'Completed')->count(),
        ];

        // If 'type' is a column directly on the documents table, remove ->with('type')
        $recentDocuments = Document::latest()->limit(5)->get();
        $recentRequests = DocumentRequest::with('document')->latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recentDocuments', 'recentRequests'));
    }
}