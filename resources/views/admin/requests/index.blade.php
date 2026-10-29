@extends('layouts.admin')

@section('title', 'Manage Requests')

@section('content')
<div class="admin-toolbar">
    <div>
        <div class="eyebrow">REQUEST MANAGEMENT</div>
        <h1>Certified Copy Requests</h1>
        <p class="muted">Review incoming requests, check uploaded IDs, assign fees, and update processing statuses.</p>
    </div>
    <a class="btn btn-light" href="{{ route('admin.dashboard') }}">Back</a>
</div>

<section class="card">
    <form method="GET" class="filter-grid">
        <div class="field wide"><label for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Tracking code, requester, contact, document..."></div>
        <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="field actions-end"><button class="btn btn-primary" type="submit">Search</button><a class="btn btn-light" href="{{ route('admin.requests.index') }}">Reset</a></div>
    </form>
</section>

<section class="card">
    @if ($requests->isEmpty())
        <div class="empty-state">No requests found.</div>
    @else
        <div class="table-wrap"><table><thead><tr><th>Tracking</th><th>Requester</th><th>Contact</th><th>Email</th><th>Document</th><th>Copies</th><th>Fee</th><th>Pickup</th><th>Valid ID</th><th>Status & Update</th></tr></thead><tbody>
        @foreach ($requests as $item)
            <tr>
                <td><strong>{{ $item->request_id }}</strong></td>
                <td>{{ $item->requester_name }}</td>
                <td>{{ $item->contact_number }}</td>
                <td>{{ $item->email ?? '—' }}</td>
                <td>{{ $item->document?->document_id ?? '—' }}<div class="small muted">{{ $item->document?->session_name }}</div></td>
                <td>{{ $item->copies }}</td>
                <td>₱{{ number_format($item->fee ?? 0, 2) }}</td>
                <td>{{ $item->pickup_date ? \Carbon\Carbon::parse($item->pickup_date)->format('M d, Y') : '—' }}</td>
                <td>
                    <div style="display: flex; flex-direction: column; gap: 0.4rem; align-items: flex-start;">
                        @if($item->valid_id_path)
                            <a href="{{ route('admin.requests.valid-id', $item) }}" target="_blank" class="btn btn-sm btn-light">View ID</a>
                        @else
                            <span class="muted">No ID</span>
                        @endif

                        @if($item->selfie_path)
                            <a href="{{ route('admin.requests.selfie', $item) }}" target="_blank" class="btn btn-sm btn-light">View Selfie</a>
                        @else
                            <span class="muted">No selfie</span>
                        @endif
                    </div>
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.requests.update', $item) }}" class="stack-form" style="gap: 0.5rem; min-width: 180px;">
                        @csrf @method('PUT')
                        <select name="status" aria-label="Status for {{ $item->request_id }}" required>
                            @foreach ($statuses as $status)<option value="{{ $status }}" @selected($item->status === $status)>{{ $status }}</option>@endforeach
                        </select>
                        <input type="number" step="0.01" name="fee" value="{{ old('fee', $item->fee) }}" placeholder="Fee (PHP)" aria-label="Expected fee for {{ $item->request_id }}">
                        <button class="btn btn-sm btn-primary btn-block" type="submit">Save & Notify</button>
                    </form>

                    @if(in_array($item->status, ['Completed', 'Cancelled', 'Rejected'], true))
                        <form method="POST" action="{{ route('admin.requests.destroy', $item) }}" style="margin-top: 0.5rem;" onsubmit="return confirm('Permanently delete request {{ $item->request_id }} and its uploaded ID and selfie? This cannot be undone.');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger btn-block" type="submit" style="background-color: #dc3545; color: white; border: none; border-radius: 4px;">Delete Request</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody></table></div>
        <div class="pagination-wrap">{{ $requests->links() }}</div>
    @endif
</section>
@endsection