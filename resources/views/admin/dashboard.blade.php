@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="admin-toolbar">
    <div>
        <h1>Administrator Dashboard</h1>
        <p class="muted">Welcome!</p>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-light" type="submit">Log out</button>
    </form>
</div>

<section class="stats-grid">
    <div class="stat-card"><span>{{ $stats['total_documents'] }}</span><small>Total Documents</small></div>
    <div class="stat-card"><span>{{ $stats['public_documents'] }}</span><small>Published Public</small></div>
    <div class="stat-card"><span>{{ $stats['pending_requests'] }}</span><small>Pending Requests</small></div>
    <div class="stat-card"><span>{{ $stats['ready_requests'] }}</span><small>Ready for Pickup</small></div>
    <div class="stat-card"><span>{{ $stats['completed_requests'] }}</span><small>Completed</small></div>
</section>

<div class="admin-nav card">
    <a class="btn btn-primary" href="{{ route('admin.documents.create') }}">+ Publish Document</a>
    <a class="btn btn-light" href="{{ route('admin.documents.index') }}">Manage Documents</a>
    <a class="btn btn-light" href="{{ route('admin.requests.index') }}">Manage Requests</a>
</div>

<div class="dashboard-grid">
    <section class="card">
        <div class="section-heading"><h2>Recent Documents</h2><a href="{{ route('admin.documents.index') }}">View all</a></div>
        @if ($recentDocuments->isEmpty())
            <div class="empty-state">No documents yet.</div>
        @else
            <div class="table-wrap"><table><thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Status</th></tr></thead><tbody>
                @foreach ($recentDocuments as $document)
                    <tr>
                        <td>{{ $document->document_id }}</td>
                        <td>{{ $document->session_name }}</td>
                        <td>{{ $document->type ?? '—' }}</td>
                        <td><span class="badge {{ $document->status === 'published' ? 'badge-completed' : 'badge-cancelled' }}">{{ ucfirst($document->status) }}</span></td>
                    </tr>
                @endforeach
            </tbody></table></div>
        @endif
    </section>

    <section class="card">
        <div class="section-heading"><h2>Recent Requests</h2><a href="{{ route('admin.requests.index') }}">View all</a></div>
        @if ($recentRequests->isEmpty())
            <div class="empty-state">No requests yet.</div>
        @else
            <div class="table-wrap"><table><thead><tr><th>Tracking ID</th><th>Requester</th><th>Status</th></tr></thead><tbody>
                @foreach ($recentRequests as $request)
                    <tr><td>{{ $request->request_id }}</td><td>{{ $request->requester_name }}</td><td><span class="badge {{ strtolower($request->status) === 'completed' ? 'badge-completed' : (strtolower($request->status) === 'cancelled' ? 'badge-cancelled' : (strtolower($request->status) === 'ready for pickup' ? 'badge-ready' : 'badge-pending')) }}">{{ $request->status }}</span></td></tr>
                @endforeach
            </tbody></table></div>
        @endif
    </section>
</div>
@endsection