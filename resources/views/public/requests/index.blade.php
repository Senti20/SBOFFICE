@extends('layouts.public')

@section('title', 'My Requests')

@section('content')
<div class="admin-toolbar">
    <div>
        <div class="eyebrow">PUBLIC RECORDS</div>
        <h1>Submitted Requests</h1>
        <p class="muted">View the status and fees of your certified copy requests.</p>
    </div>
    <a class="btn btn-light" href="{{ route('home') }}">Back to Archive</a>
</div>

<section class="card">
    @if ($requests->isEmpty())
        <div class="empty-state">No requests have been submitted yet.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Requester</th>
                        <th>Document</th>
                        <th>Copies</th>
                        <th>Fee</th>
                        <th>Pickup Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $req)
                        <tr>
                            <td><strong>{{ $req->request_id }}</strong></td>
                            <td>{{ $req->requester_name }}</td>
                            <td>{{ $req->document->session_name ?? $req->document_id }}</td>
                            <td>{{ $req->copies }}</td>
                            <td>₱{{ number_format($req->fee ?? 0, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($req->pickup_date)->format('M d, Y') }}</td>
                            <td>
                                <span class="badge {{ $req->status === 'Completed' ? 'badge-completed' : ($req->status === 'Cancelled' ? 'badge-cancelled' : ($req->status === 'Ready for Pickup' ? 'badge-ready' : 'badge-pending')) }}">{{ $req->status }}</span>
                            </td>
                            <td class="actions-cell">
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    @if($req->status === 'Pending')
                                        <form method="POST" action="{{ route('public.requests.cancel', $req) }}" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-light" type="submit">Cancel</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('public.requests.destroy', $req) }}" onsubmit="return confirm('Are you sure you want to delete this request record?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit" style="background-color: #dc3545; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $requests->links() }}</div>
    @endif
</section>
@endsection