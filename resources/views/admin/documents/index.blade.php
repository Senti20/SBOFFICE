
@extends('layouts.admin')

@section('title', 'Manage Documents')

@section('content')
<div class="admin-toolbar">
    <div>
        <div class="eyebrow">DOCUMENT MANAGEMENT</div>
        <h1>Documents</h1>
        <p class="muted">Search, review, edit, publish, or archive official records.</p>
    </div>

    <div style="display: flex; gap: 10px;">
        <a class="btn btn-light" href="{{ route('admin.dashboard') }}">Back</a>
        <a class="btn btn-primary" href="{{ route('admin.documents.create') }}">+ Publish Document</a>
    </div>
</div>

<section class="card">
    <form method="GET" class="filter-grid">
        <div class="field wide">
            <label for="q">Search</label>
            <input
                id="q"
                name="q"
                value="{{ request('q') }}"
                placeholder="ID, session name, sponsors, session ID, tags..."
            >
        </div>

        <div class="field">
            <label for="type">Type</label>
            <select id="type" name="type">
                <option value="">All</option>
                @foreach ($types as $type)
                    <option
                        value="{{ $type->name }}"
                        @selected(request('type') === $type->name)
                    >
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <option value="published" @selected(request('status') === 'published')>
                    Published
                </option>
                <option value="archived" @selected(request('status') === 'archived')>
                    Archived
                </option>
            </select>
        </div>

        <div class="field actions-end">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-light" href="{{ route('admin.documents.index') }}">Reset</a>
        </div>
    </form>
</section>

<section class="card">
    @if ($documents->isEmpty())
        <div class="empty-state">No documents found.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Session Name</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Visibility</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($documents as $document)
                        <tr>
                            <td>
                                <strong>{{ $document->document_id }}</strong>
                            </td>

                            <td>
                                {{ $document->session_name }}
                                <div class="small muted">{{ $document->filename }}</div>
                            </td>

                            <td>{{ $document->type ?? '—' }}</td>

                            <td>
                                {{ $document->session_date
                                    ? \Carbon\Carbon::parse($document->session_date)->format('M d, Y')
                                    : '—' }}
                            </td>

                            <td>
                                {{ $document->is_public ? 'Public' : 'Private' }}
                            </td>

                            <td>
                                <span class="badge {{ $document->status === 'published' ? 'badge-completed' : 'badge-cancelled' }}">
                                    {{ ucfirst($document->status) }}
                                </span>
                            </td>

                            <td class="actions-cell">
                                <div style="display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">

                                    <a
                                        class="btn btn-sm btn-view"
                                        href="{{ route('admin.documents.file', $document) }}"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        View
                                    </a>

                                    <a
                                        class="btn btn-sm btn-light"
                                        href="{{ route('admin.documents.edit', $document) }}"
                                    >
                                        Edit
                                    </a>

                                    {{-- Archive: retain the record and PDF --}}
                                    @if (! $document->trashed() && $document->status !== 'archived')
                                        <form
                                            method="POST"
                                            action="{{ route('admin.documents.archive', $document->id) }}"
                                            class="inline-form"
                                            onsubmit="return confirm('Archive this document? It will be hidden from the public but retained in the system.');"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                class="btn btn-sm btn-danger"
                                                type="submit"
                                            >
                                                Archive
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Permanently delete the record and PDF --}}
                                    <form
                                        method="POST"
                                        action="{{ route('admin.documents.destroy', $document) }}"
                                        class="inline-form"
                                        onsubmit="return confirm('WARNING: This will permanently delete the document record and its PDF file. This cannot be undone. Proceed?');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            class="btn btn-sm btn-danger"
                                            type="submit"
                                            style="background: #7f1d1d; border-color: #7f1d1d; color: #fff;"
                                        >
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $documents->links() }}
        </div>
    @endif
</section>
@endsection
