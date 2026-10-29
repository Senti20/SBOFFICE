@extends('layouts.admin')

@section('title', 'Publish Document')

@section('content')
<div class="admin-toolbar">
    <div><h1>Publish Document</h1></div>
    <a class="btn btn-light" href="{{ route('admin.documents.index') }}">Back</a>
</div>

<section class="card narrow-card">
    <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" class="stack-form">
        @csrf
        <label for="session_name">Session Name / Title</label>
        <input id="session_name" name="session_name" value="{{ old('session_name') }}" required maxlength="255" placeholder="e.g. Annual Budget">

        <div class="two-col">
            <div>
                <label for="type">Document Type</label>
                <select id="type" name="type" required>
                    <option value="">Select type</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->name }}" @selected(old('type') == $type->name)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="session_id">Session ID</label>
                <input id="session_id" name="session_id" value="{{ old('session_id') }}" placeholder="2026-001" maxlength="100">
            </div>
        </div>

        <div class="two-col">
            <div>
                <label for="session_date">Session Date</label>
                <input id="session_date" name="session_date" type="date" value="{{ old('session_date', now()->toDateString()) }}" required>
            </div>
            <div>
                <label for="sponsors">Sponsor/s</label>
                <input id="sponsors" name="sponsors" value="{{ old('sponsors', 'Councilor') }}" maxlength="255">
            </div>
        </div>

        <label for="tags">Tags</label>
        <input id="tags" name="tags" value="{{ old('tags') }}" placeholder="title, barangay,etc.">

        <label class="checkbox-row"><input type="checkbox" name="is_public" value="1" @checked(old('is_public', true))><span>Publish this document to the public archive</span></label>

        <label for="pdf">PDF File <span class="muted">(maximum 10 MB)</span></label>
        <input id="pdf" name="pdf" type="file" accept="application/pdf,.pdf" required>

        <button class="btn btn-primary" type="submit">Upload & Publish</button>
    </form>
</section>
@endsection