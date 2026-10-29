@extends('layouts.public')

@section('title', 'Public Archive')

@section('content')
<section class="hero card">
    <div>
        <div class="eyebrow">PUBLIC RECORDS</div>
        <h1>Search Official SB Documents</h1>
        <p class="muted">Find published resolutions, minutes, ordinances, and other public records.</p>
    </div>
</section>

<section class="card">
    <form method="GET" action="{{ route('home') }}" class="filter-grid">
        <!-- General Keyword/Tag Search -->
        <div class="field wide">
            <label for="q">Keyword</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Search topics, tags, or document text...">
        </div>
        
        <!-- Filter 1: Type -->
        <div class="field">
            <label for="type">Document Type</label>
            <select id="type" name="type">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->name }}" @selected(request('type') === $type->name)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field actions-end">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-light" href="{{ route('home') }}">Reset</a>
        </div>
    </form>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h2>Available Documents</h2>
            <p class="muted">{{ $documents->total() }} published record(s)</p>
        </div>
    </div>

    @if ($documents->isEmpty())
        <div class="empty-state">No public documents matched your search.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Session Name</th>
                        <th>Type</th>
                        <th>Session Date</th>
                        <th>Sponsors</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $document)
                        <tr>
                            <td><strong>{{ $document->session_name }}</strong></td>
                            <td>{{ $document->type ?? 'Uncategorized' }}</td>
                            <td>{{ $document->session_date ? \Carbon\Carbon::parse($document->session_date)->format('M d, Y') : '—' }}</td>
                            <td>{{ $document->sponsors ?? '—' }}</td>
                            <td class="actions-cell">
                                <a class="btn btn-sm btn-view" href="{{ route('public.documents.file', $document) }}" target="_blank" rel="noopener">Read PDF</a>
                                <a class="btn btn-sm btn-light" href="{{ route('public.requests.create', ['document' => $document->document_id]) }}">Request Copy</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $documents->links() }}</div>
    @endif
</section>
<script src="{{ asset('js/public.js') }}"></script>
@endsection